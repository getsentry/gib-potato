<?php
declare(strict_types=1);

namespace App\Test\TestCase;

use Cake\Http\TestSuite\HttpClientTrait;
use Cake\ORM\Locator\LocatorAwareTrait;

trait FactoryTrait
{
    use LocatorAwareTrait;
    use HttpClientTrait;

    protected function login(string $userId = '00000000-0000-0000-0000-000000000001'): void
    {
        $user = $this->fetchTable('Users')->get($userId);
        $this->session([
            'Auth' => $user,
        ]);
    }

    protected function usePotalToken(): void
    {
        $this->configRequest([
            'headers' => [
                'Authorization' => env('POTAL_TOKEN'),
            ],
        ]);
    }

    public function mockSlackClientGetUser(string $userId): void
    {
        $this->mockClientGet(
            'https://slack.com/api/users.info?user=' . $userId,
            $this->newClientResponse(200, [], json_encode([
                'ok' => true,
                'user' => [
                    'id' => $userId,
                    'deleted' => false,
                    'real_name' => 'User ' . $userId,
                    'profile' => [
                        'image_72' => 'https://example.com/' . $userId . '.jpg',
                    ],
                    'is_bot' => false,
                ],
            ]))
        );
    }

    public function mockSlackClientPostMessage(): void
    {
        $this->mockClientPost(
            'https://slack.com/api/chat.postMessage',
            $this->newClientResponse(200, [], json_encode([
                'ok' => true,
            ]))
        );
    }

    public function mockSlackClientPublishView(): void
    {
        $this->mockClientPost(
            'https://slack.com/api/views.publish',
            $this->newClientResponse(200, [], json_encode([
                'ok' => true,
            ]))
        );
    }
}
