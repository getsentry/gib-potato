<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * GenerateShares command.
 */
class GenerateSharesCommand extends Command
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
        $sharesTable = $this->fetchTable('Shares');

        $io->out('Generating new shares');

        $stocks = $stocksTable->find()->all();
        foreach ($stocks as $stock) {
            // TODO for now, we generate 100x each
            $shares = $sharesTable->newEntities(array_fill(0, 100, ['stock_id' => $stock->id]));
            $sharesTable->saveManyOrFail($shares);

            $io->out(sprintf(
                'Generated %s shares of type %s',
                count($shares),
                $stock->symbol,
            ));
        }

        $io->success("\n[DONE]");
    }
}
