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
                'id' => '00000000-0000-0000-0000-000000000001',
                'status' => 'active',
                'slack_user_id' => 'U001',
                'slack_name' => 'User One',
                'slack_picture' => 'https://example.com/user1.jpg',
                'created' => '2023-01-01 00:00:00',
                'modified' => '2023-01-01 00:00:00',
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000002',
                'status' => 'active',
                'slack_user_id' => 'U002',
                'slack_name' => 'User Two',
                'slack_picture' => 'https://example.com/user2.jpg',
                'created' => '2023-01-01 00:00:00',
                'modified' => '2023-01-01 00:00:00',
            ],
        ];
        parent::init();
    }
}
