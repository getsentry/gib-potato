<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MessagesFixture
 */
class MessagesFixture extends TestFixture
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
                'id' => '3fc8f071-12ae-4a89-8101-e82b5e2cb3aa',
                'sender_user_id' => '5fbf8d8d-fc8e-41b0-97bf-d708cbee4d9d',
                'reciever_user_id' => '8642501f-3e28-4051-b9a6-5ef7f7e01f10',
                'amount' => 1,
                'created' => '2022-08-22 11:48:05',
            ],
        ];
        parent::init();
    }
}
