<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Exception;

/**
 * GenerateInitialShareprices command.
 */
class GenerateInitialSharePricesCommand extends Command
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
        $io->out('Generating initial share prices');

        $configTable = $this->fetchTable('Config');
        $config = $configTable->find()->firstOrFail();
        if ($config->market_initalized === true) {
            throw new Exception('Market already initialized');
        }

        $stocksTable = $this->fetchTable('Stocks');
        $sharePricesTable = $this->fetchTable('SharePrices');

        $stocks = $stocksTable->find()->all();

        foreach ($stocks as $stock) {
            $sharePrice = $sharePricesTable->newEntity([
                'stock_id' => $stock->id,
                'price' => $stock->initial_share_price,
            ]);
            $sharePricesTable->saveOrFail($sharePrice);

            $io->out(sprintf(
                'Generated %s initial share price',
                $stock->symbol,
            ));
        }
    }
}
