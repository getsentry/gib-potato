<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\Trade;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Exception;

/**
 * GenerateInitialTrades command.
 */
class GenerateInitialTradesCommand extends Command
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
        $configTable = $this->fetchTable('Config');
        $config = $configTable->find()->firstOrFail();
        if ($config->market_initalized === true) {
            throw new Exception('Market already initialized');
        }

        $usersTable = $this->fetchTable('Users');
        $sharesTable = $this->fetchTable('Shares');
        $tradesTable = $this->fetchTable('Trades');

        $io->out('Generating initial trades');

        $data = [];

        $shares = $sharesTable->find()
            ->contain('Stocks')
            ->all();

        foreach ($shares as $share) {
            $data[] = [
                'user_id' => $usersTable->findBySlackUserId(env('POTATO_SLACK_USER_ID'))->first()->id,
                'share_id' => $share->id,
                'stock_id' => $share->stock_id,
                'proposed_price' => $share->stock->initial_share_price,
                'status' => Trade::STATUS_PENDING,
                'type' => Trade::TYPE_SELL,
            ];
        }

        $shares = $tradesTable->newEntities($data);
        $tradesTable->saveManyOrFail($shares);

        $io->out(sprintf(
            'Generated %s initial trades',
            count($shares),
        ));
    }
}
