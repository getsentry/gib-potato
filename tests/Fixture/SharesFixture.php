<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SharesFixture
 */
class SharesFixture extends TestFixture
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
                'created' => 1743037897,
                'user_id' => 'ecd817d7-98fd-4bfa-8b60-641a31cd7d6b',
            ],
        ];
        parent::init();
    }
}
