<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CreditsFixture
 */
class CreditsFixture extends TestFixture
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
                'user_id' => '62626bea-bcfd-4eac-9d7b-1ab87a0bd3fe',
                'amount' => 1,
                'created' => 1743461215,
            ],
        ];
        parent::init();
    }
}
