<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Exception;

/**
 * OpenStockMarket command.
 */
class OpenStockMarketCommand extends Command
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
            return;
        }

        $stocksTable = $this->fetchTable('Stocks');
        if ($stocksTable->find()->count() === 0) {
            throw new Exception('No stocks added yet');
        }

        $this->executeCommand(GenerateInitialSharesCommand::class);
        $this->executeCommand(GenerateInitialSharePricesCommand::class);
        $this->executeCommand(GenerateInitialTradesCommand::class);
        $this->executeCommand(AnnounceStockMarketCommand::class);

        $config = $configTable->patchEntity($config, [
            'market_initalized' => true,
            'market_open' => true,
        ]);
        $configTable->saveOrFail($config);

        $io->success("\n[DONE]");
    }
}
