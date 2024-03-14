<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\FactoryTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\EventsController Test Case
 *
 * @uses \App\Controller\EventsController
 */
class EventsControllerTest extends TestCase
{
    use FactoryTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Messages',
        'app.QuickWins',
        'app.Users',
    ];

    public function setUp(): void
    {
        $this->requestAsJson();
        $this->usePotalToken();

        parent::setUp();
    }

    public function testTypeMessage(): void
    {
        $this->mockSlackClientPostMessage();

        $this->post('/events', json_encode([
            'type' => 'message',
            'amount' => 1,
            'sender' => 'U1111',
            'receivers' => [
                'U2222',
            ],
            'channel' => 'C1111',
            'text' => '<@U2222> :potato:',
            'reaction' => 'potato',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();
        $this->assertHeader('Content-Type', 'application/json');

        $messages = $this->fetchTable('Messages')
            ->find()
            ->all();

        $this->assertSame(1, $messages->count());
        $this->assertSame('00000000-0000-0000-0000-000000000001', $messages->first()->sender_user_id);
        $this->assertSame('00000000-0000-0000-0000-000000000002', $messages->first()->receiver_user_id);
        $this->assertSame(1, $messages->first()->amount);
    }

    public function testTypeMessageQuickWin(): void
    {
        $this->mockSlackClientPostMessage();

        $this->post('/events', json_encode([
            'type' => 'message',
            'amount' => 1,
            'sender' => 'U1111',
            'receivers' => [
                'U2222',
            ],
            'channel' => 'C1111',
            'text' => '<@U2222> :potato: #quickwin',
            'reaction' => 'potato',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();
        $this->assertHeader('Content-Type', 'application/json');

        $messages = $this->fetchTable('Messages')
            ->find()
            ->all();

        $quickWins = $this->fetchTable('QuickWins')
            ->find()
            ->all();

        $this->assertSame(1, $messages->count());
        $this->assertSame('00000000-0000-0000-0000-000000000001', $messages->first()->sender_user_id);
        $this->assertSame('00000000-0000-0000-0000-000000000002', $messages->first()->receiver_user_id);
        $this->assertSame(1, $messages->first()->amount);

        $this->assertSame(1, $quickWins->count());
        $this->assertSame('00000000-0000-0000-0000-000000000001', $quickWins->first()->sender_user_id);
        $this->assertSame('<@U2222> :potato: #quickwin', $quickWins->first()->message);
        $this->assertSame('https://example.com/permalink', $quickWins->first()->permalink);
    }

    public function testTypeDirectMessage(): void
    {
        $this->mockSlackClientPostMessage();

        $this->post('/events', json_encode([
            'type' => 'direct_message',
            'sender' => 'U1111',
            'channel' => 'D1111',
            'text' => 'potato',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
        ]));

        $this->assertResponseOk();
        $this->assertHeader('Content-Type', 'application/json');
    }

    public function testTypeReactionAdded(): void
    {
        $this->mockSlackClientPostMessage();

        $this->post('/events', json_encode([
            'type' => 'reaction_added',
            'amount' => 1,
            'sender' => 'U1111',
            'receivers' => [
                'U2222',
            ],
            'channel' => 'C1111',
            'text' => '<@U2222> :potato:',
            'reaction' => 'potato',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();
        $this->assertHeader('Content-Type', 'application/json');

        $messages = $this->fetchTable('Messages')
            ->find()
            ->all();

        $this->assertSame(1, $messages->count());
        $this->assertSame('00000000-0000-0000-0000-000000000001', $messages->first()->sender_user_id);
        $this->assertSame('00000000-0000-0000-0000-000000000002', $messages->first()->receiver_user_id);
        $this->assertSame(1, $messages->first()->amount);
    }

    public function testTypeAppMentionEvent(): void
    {
        $this->post('/events', json_encode([
            'type' => 'app_mention',
            'sender' => 'U1111',
            'channel' => 'C1111',
            'text' => '<@U3333> Hey!',
            'event_timestamp' => '1672531200',
            'bot_id' => 'B1111',
        ]));

        $this->assertResponseOk();
        $this->assertHeader('Content-Type', 'application/json');
    }

    public function testTypeAppHomeOpened(): void
    {
        $this->mockSlackClientPublishView();

        $this->post('/events', json_encode([
            'type' => 'app_home_opened',
            'user' => 'U1111',
            'tab' => 'home',
            'event_timestamp' => '1672531200',
        ]));

        $this->assertResponseOk();
        $this->assertHeader('Content-Type', 'application/json');
    }
}
