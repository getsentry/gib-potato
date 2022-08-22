<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
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
                'id' => 'ed16b6aa-5572-4a82-868c-17182d59cbee',
                'username' => 'Lorem ipsum dolor sit amet',
                'created' => '2022-08-22 11:47:50',
                'modified' => '2022-08-22 11:47:50',
            ],
        ];
        parent::init();
    }
}
