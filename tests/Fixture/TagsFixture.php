<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TagsFixture
 */
class TagsFixture extends TestFixture
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
                'id' => '92d4083d-718f-4dd5-907e-5503bc1cac1b',
                'name' => '#quickwin',
            ],
        ];
        parent::init();
    }
}
