<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TradesFixture
 */
class TradesFixture extends TestFixture
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
                'user_id' => 'c0958a8b-6684-4ffe-a534-4fc5c75028d7',
                'share_id' => 1,
                'price' => 1,
                'status' => 'Lorem ipsum dolor sit amet',
                'type' => 'Lorem ipsum dolor sit amet',
                'created' => 1743055454,
            ],
        ];
        parent::init();
    }
}
