<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;

/**
 * Stonks command.
 */
class StonksCommand extends Command
{
    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        return $parser;
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $stocksTable = $this->fetchTable('Stocks');
        $sharePricesTable = $this->fetchTable('SharePrices');

        $io->out('Recalculating share prices');

        $stocks = $stocksTable->find()->all();

        $time = new DateTime('2025-04-01 07:00:00');
        for ($i = 0; $i < 96; $i++) {
            foreach ($stocks as $stock) {
                $latestSharePrice = $sharePricesTable->find()
                    ->where(['stock_id' => $stock->id])
                    ->orderBy(['id' => 'DESC'])
                    ->first()
                    ->price ?? random_int(10, 100);

                $base = random_int(-25, 25); // 0 - 25%
                $newSharePrice = (int) round($latestSharePrice * (1 + ($base / 100)), 0);

                $sharePrice = $sharePricesTable->newEntity([
                    'stock_id' => $stock->id,
                    'price' => $newSharePrice,
                    'created' => $time,
                ]);
                $sharePricesTable->saveOrFail($sharePrice);

                $io->out(sprintf(
                    'Stock %s went from %s to %s (%s%%)',
                    $stock->symbol,
                    $latestSharePrice,
                    $newSharePrice,
                    $base,
                ));
            }

            $time = $time->modify('+15 minutes');
        }

        $io->success("\n[DONE]");
    }
}
