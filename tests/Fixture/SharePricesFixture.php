<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SharePricesFixture
 */
class SharePricesFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'stock_id' => 1,
                'created' => 1743037854,
                'price' => 1,
            ],
        ];
        parent::init();
    }
}
