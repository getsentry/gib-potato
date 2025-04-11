<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\Trade;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Exception;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use function Cake\Collection\collection;
use function Cake\Core\env;
use function Sentry\withMonitor;

/**
 * ExecuteTrades command.
 */
class ExecuteTradesCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(static::getDescription());
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        withMonitor(
            slug: 'execute-trades',
            callback: fn() => $this->_execute($args, $io),
            monitorConfig: new MonitorConfig(
                schedule: new MonitorSchedule(
                    type: MonitorSchedule::TYPE_CRONTAB,
                    value: '*/5 * * * *',
                ),
                checkinMargin: 1,
                maxRuntime: 4,
                timezone: 'UTC',
            ),
        );
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    protected function _execute(Arguments $args, ConsoleIo $io)
    {
        $io->out('Executing trades');

        $configTable = $this->fetchTable('Config');
        $config = $configTable->find()->firstOrFail();
        if ($config->market_open === false) {
            throw new Exception('Market currently closed');
        }

        $usersTable = $this->fetchTable('Users');
        $stocksTable = $this->fetchTable('Stocks');
        $sharesTable = $this->fetchTable('Shares');
        $sharePricesTable = $this->fetchTable('SharePrices');
        $tradesTable = $this->fetchTable('Trades');

        $expiredTrades = $tradesTable->find()
            ->where([
                'status' => Trade::STATUS_PENDING,
                'user_id IS NOT' => $usersTable->findBySlackUserId(env('POTATO_SLACK_USER_ID'))->first()->id,
                'created <=' => new DateTime('20 minutes ago'),
            ])
            ->all();

        foreach ($expiredTrades as $expiredTrade) {
            $expiredTrade = $tradesTable->patchEntity($expiredTrade, [
                'status' => Trade::STATUS_EXPIRED,
            ]);
            $tradesTable->saveOrFail($expiredTrade);

            $io->out(sprintf(
                'Marked trade %s as expired',
                $expiredTrade->id,
            ));
        }

        $trades = $tradesTable->find()
            ->where([
                'status' => Trade::STATUS_PENDING,
            ])
            ->contain('Stocks')
            ->orderBy(['trades.id' => 'ASC'])
            ->all();

        $buyTrades = $trades->filter(function ($trade) {
            return $trade->type === Trade::TYPE_BUY;
        });
        $sellTrades = $trades->filter(function ($trade) {
            return $trade->type === Trade::TYPE_SELL;
        });

        $sellTrades = $sellTrades->map(function ($value) {
            return [
                'id' => $value->id,
                'user_id' => $value->user_id,
                'stock_id' => $value->stock_id,
                'proposed_price' => $value->proposed_price,
                'type' => $value->type,
            ];
        })->toArray();
        $buyTrades = $buyTrades->map(function ($value) {
            return [
                'id' => $value->id,
                'user_id' => $value->user_id,
                'stock_id' => $value->stock_id,
                'proposed_price' => $value->proposed_price,
                'type' => $value->type,
            ];
        })->toArray();

        $executedTradeIds = [
            -1,
        ];

        foreach ($sellTrades as $sellTrade) {
            $match = $this->_findMatchingTrade($buyTrades, $sellTrade);
            if ($match) {
                // Validate spendable amounts

                $sellTradeEntity = $tradesTable->findById($sellTrade['id'])->firstOrFail();
                $buyTradeEntity = $tradesTable->findById($match['id'])->firstOrFail();

                // Do not allow to sell to yourself
                if ($sellTradeEntity->user_id === $buyTradeEntity->user_id) {
                    continue;
                }

                $sellTradeEntity = $tradesTable->patchEntity($sellTradeEntity, [
                    'price' => $sellTrade['proposed_price'],
                    'status' => Trade::STATUS_DONE,
                ]);
                $tradesTable->saveOrFail($sellTradeEntity);

                $buyTradeEntity = $tradesTable->patchEntity($buyTradeEntity, [
                    'price' => $sellTrade['proposed_price'],
                    'status' => Trade::STATUS_DONE,
                    'share_id' => $sellTradeEntity->share_id,
                ]);
                $tradesTable->saveOrFail($buyTradeEntity);

                $shareEntity = $sharesTable->findById($sellTradeEntity->share_id)->firstOrFail();
                $shareEntity = $sharesTable->patchEntity($shareEntity, [
                    'user_id' => $buyTradeEntity->user_id,
                ]);
                $sharesTable->saveOrFail($shareEntity);

                $io->out(sprintf(
                    'Matched and executed sell trade %s with buy trade %s',
                    $sellTradeEntity->id,
                    $buyTradeEntity->id,
                ));

                $executedTradeIds[] = $sellTrade['id'];
                $executedTradeIds[] = $match['id'];
            }
        }

        $doneTrades = $tradesTable->find()
            ->where([
                'trades.id IN' => $executedTradeIds,
                'trades.status' => Trade::STATUS_DONE,
            ])
            ->contain('Stocks')
            ->orderBy(['trades.id' => 'ASC'])
            ->all();

        $doneBuyTrades = $doneTrades->filter(function ($doneTrade) {
            return $doneTrade->type === Trade::TYPE_BUY;
        });
        $doneSellTrades = $doneTrades->filter(function ($doneTrade) {
            return $doneTrade->type === Trade::TYPE_SELL;
        });

        $doneBuyTrades = $doneBuyTrades->groupBy('stock_id')->toArray();
        $doneSellTrades = $doneSellTrades->groupBy('stock_id')->toArray();

        $doneBuyTradesAverage = [];
        foreach ($doneBuyTrades as $stockId => $doneBuyTradesByStock) {
            $doneBuyTradesAverageByStock = collection($doneBuyTradesByStock)->avg('price');
            $doneBuyTradesAverage[$stockId] = [
                'stock_id' => $stockId,
                'average' => $doneBuyTradesAverageByStock,
            ];
        }

        $doneSellTradesAverage = [];
        foreach ($doneSellTrades as $stockId => $doneSellTradesByStock) {
            $doneSellTradesAverageByStock = collection($doneSellTradesByStock)->avg('price');
            $doneSellTradesAverage[$stockId] = [
                'stock_id' => $stockId,
                'average' => $doneSellTradesAverageByStock,
            ];
        }

        $newSharePrices = [];

        $stocks = $stocksTable->find()
            ->contain('SharePrices', function (SelectQuery $query) {
                return $query
                    ->orderBy(['SharePrices.id' => 'DESC']);
            })
            ->all();

        foreach ($stocks as $stock) {
            $sharePriceBuyAverage = $doneBuyTradesAverage[$stock->id]['average'] ?? $stock->share_prices[0]->price;
            $sharePriceSellAverage = $doneSellTradesAverage[$stock->id]['average'] ?? $stock->share_prices[0]->price;

            $average = ((int)round($sharePriceBuyAverage + $sharePriceSellAverage)) / 2;

            $newSharePrices[] = [
                'stock_id' => $stock->id,
                'price' => $average,
            ];

            $io->out(sprintf(
                'New share price for %s as %s',
                $stock->symbol,
                $average,
            ));
        }

        $sharePrices = $sharePricesTable->newEntities($newSharePrices);
        $sharePricesTable->saveManyOrFail($sharePrices);

        $io->success("\n[DONE]");
    }

    /**
     * @return array|null
     */
    protected function _findMatchingTrade(array &$buyTrades, array $sellTrade): ?array
    {
        $candidates = [];
        foreach ($buyTrades as $key => $buyTrade) {
            if (
                $buyTrade['stock_id'] === $sellTrade['stock_id'] &&
                $buyTrade['proposed_price'] >= $sellTrade['proposed_price']
            ) {
                $candidates[] = array_merge($buyTrade, ['original_key' => $key]);
            }
        }

        if (empty($candidates)) {
            return null;
        }

        $candidates = collection($candidates);
        $candidates = $candidates->sortBy('proposed_price');
        $match = $candidates->first();

        unset($buyTrades[$match['original_key']]);

        return $match;
    }
}
