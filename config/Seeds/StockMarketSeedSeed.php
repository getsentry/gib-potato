<?php
declare(strict_types=1);

use Cake\I18n\DateTime;
use Migrations\BaseSeed;

/**
 * StockMarketSeed seed.
 */
class StockMarketSeedSeed extends BaseSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * https://book.cakephp.org/migrations/4/en/seeding.html
     *
     * @return void
     */
    public function run(): void
    {
        $config = $this->fetchAll('SELECT * FROM config');
        if (count($config) > 0) {
            return;
        }

        $stocks = $this->fetchAll('SELECT * FROM stocks');
        if (count($stocks) > 0) {
            return;
        }

        $config = [
            'market_open' => false,
            'market_initalized' => false,
        ];

        $table = $this->table('config');
        $table->insert($config)->save();

        $stocks = [
            [
                'symbol' => 'SFO',
                'description' => 'San Francisco',
                'initial_share_quantity' => 250,
                'initial_share_price' => 55,
                'created' => DateTime::now(),
            ],
            [
                'symbol' => 'YYZ',
                'description' => 'Toronto',
                'initial_share_quantity' => 200,
                'initial_share_price' => 42,
                'created' => DateTime::now(),
            ],
            [
                'symbol' => 'VIE',
                'description' => 'Vienna',
                'initial_share_quantity' => 350,
                'initial_share_price' => 18,
                'created' => DateTime::now(),
            ],
            [
                'symbol' => 'SEA',
                'description' => 'Seattle',
                'initial_share_quantity' => 100,
                'initial_share_price' => 8,
                'created' => DateTime::now(),
            ],
            [
                'symbol' => 'AMS',
                'description' => 'Amsterdam',
                'initial_share_quantity' => 75,
                'initial_share_price' => 5,
                'created' => DateTime::now(),
            ],
            [
                'symbol' => 'REM',
                'description' => 'Remote',
                'initial_share_quantity' => 210,
                'initial_share_price' => 35,
                'created' => DateTime::now(),
            ],
        ];

        $table = $this->table('stocks');
        $table->insert($stocks)->save();
    }
}
