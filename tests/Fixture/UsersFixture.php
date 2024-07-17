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
                'role' => 'user',
                'slack_user_id' => 'U1111',
                'slack_name' => 'User U1111',
                'slack_picture' => 'https://example.com/U1111.jpg',
                'slack_is_bot' => 0,
                'slack_time_zone' => 'Europe/Amsterdam',
                'created' => '2023-01-01 00:00:00',
                'modified' => '2023-01-01 00:00:00',
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000002',
                'status' => 'active',
                'role' => 'user',
                'slack_user_id' => 'U2222',
                'slack_name' => 'User U2222',
                'slack_picture' => 'https://example.com/U2222.jpg',
                'slack_is_bot' => 0,
                'slack_time_zone' => 'America/Toronto',
                'created' => '2023-01-01 00:00:00',
                'modified' => '2023-01-01 00:00:00',
            ],
            [
                'id' => '00000000-0000-0000-0000-000000000003',
                'status' => 'active',
                'role' => 'user',
                'slack_user_id' => 'U3333',
                'slack_name' => 'User U3333',
                'slack_picture' => 'https://example.com/U3333.jpg',
                'slack_is_bot' => 0,
                'slack_time_zone' => 'America/Los_Angeles',
                'created' => '2023-01-01 00:00:00',
                'modified' => '2023-01-01 00:00:00',
            ],
        ];
        parent::init();
    }
}
