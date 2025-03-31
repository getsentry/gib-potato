<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\Trade;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;

/**
 * GenerateSharePrices command.
 */
class GenerateSharePricesCommand extends Command
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
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $stocksTable = $this->fetchTable('Stocks');
        $sharePricesTable = $this->fetchTable('SharePrices');
        $tradesTable = $this->fetchTable('Trades');

        $io->out('Generating new share prices');

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

        if (empty($buyTrades) || $sellTrades) {
            $io->out(
                sprintf("Can't perform any trades. Got %s buy and %s sell trades",
                    $buyTrades->count(),
                    $sellTrades->count(),
                )
            );
        }

        $sell = $sellTrades->map(function ($value) {
            return [
                'id' => $value->id,
                'stock_id' => $value->stock_id,
                'proposed_price' => $value->proposed_price,
                'type' => $value->type,
            ];
        })->toArray();
        $buy = $buyTrades->map(function ($value) {
            return [
                'id' => $value->id,
                'stock_id' => $value->stock_id,
                'proposed_price' => $value->proposed_price,
                'type' => $value->type,
            ];
        })->toArray();

        debug($sell);
        debug($buy);
        exit;

        $io->success("\n[DONE]");
    }

    protected function _findClosest($haystack, $needle) {
        $left = 0;
        $right = count($haystack) - 1;
        while ($left < $right) {
            if (abs($haystack[$left] - $needle) <= abs($haystack[$right] - $needle)) {
                $right--;
            } else {
                $left++;
            }
        }
        return $haystack[$left];
    }
}
