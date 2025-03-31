<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * GenerateInitialShares command.
 */
class GenerateInitialSharesCommand extends Command
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
        $usersTable = $this->fetchTable('Users');

        $io->out('Generating initial shares');

        $stocks = $stocksTable->find()->all();
        foreach ($stocks as $stock) {
            $data = [];
            for ($i = 0; $i < $stock->initial_share_quantity; $i++) {
                $data[] = [
                    'stock_id' => $stock->id,
                    'user_id' => $usersTable->findBySlackUserId(env('POTATO_SLACK_USER_ID'))->first()->id,
                ];
            }

            $shares = $sharesTable->newEntities($data);
            $sharesTable->saveManyOrFail($shares);

            $io->out(sprintf(
                'Generated %s initial shares of type %s',
                count($shares),
                $stock->symbol,
            ));
        }

        $io->success("\n[DONE]");
    }
}
