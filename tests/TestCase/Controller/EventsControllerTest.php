<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Entity\Message;
use App\Test\TestCase\FactoryTrait;
use Cake\Chronos\Chronos;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\EventsController Test Case
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
        'app.Users',
    ];

    public function setUp(): void
    {
        $this->requestAsJson();
        $this->usePotalToken();

        parent::setUp();
    }

    public function tearDown(): void
    {
        Chronos::setTestNow();

        parent::tearDown();
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

    public function testTypeReactionAddedSuperPotatoWithoutBalance(): void
    {
        $this->mockSlackClientPostEphemeral();

        $this->post('/events', json_encode($this->superPotatoReactionEvent()));

        $this->assertResponseOk();
        $this->assertSame(0, $this->fetchTable('Messages')->find()->count());
    }

    public function testTypeReactionAddedSuperPotato(): void
    {
        $this->mockSlackClientPostMessage();

        // 5 past days with 1 potato given each = 20 unspent potato = 1 super potato
        $messagesTable = $this->fetchTable('Messages');
        for ($day = 1; $day <= 5; $day++) {
            $messagesTable->saveOrFail($messagesTable->newEntity([
                'sender_user_id' => '00000000-0000-0000-0000-000000000001',
                'receiver_user_id' => '00000000-0000-0000-0000-000000000003',
                'type' => Message::TYPE_POTATO,
                'amount' => 1,
                'created' => (new DateTime(Message::SUPER_POTATO_START, 'UTC'))->addDays($day)->addHours(12),
            ], ['accessibleFields' => ['*' => true]]));
        }
        Chronos::setTestNow((new Chronos(Message::SUPER_POTATO_START, 'UTC'))->addDays(10));

        $this->post('/events', json_encode($this->superPotatoReactionEvent()));

        $this->assertResponseOk();

        $message = $messagesTable->find()
            ->where(['type' => Message::TYPE_SUPER_POTATO])
            ->firstOrFail();

        $this->assertSame('00000000-0000-0000-0000-000000000001', $message->sender_user_id);
        $this->assertSame('00000000-0000-0000-0000-000000000002', $message->receiver_user_id);
        $this->assertSame(Message::SUPER_POTATO_VALUE, $message->amount);
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

    private function superPotatoReactionEvent(): array
    {
        return [
            'type' => 'reaction_added',
            'amount' => 1,
            'sender' => 'U1111',
            'receivers' => [
                'U2222',
            ],
            'channel' => 'C1111',
            'text' => '<@U2222> :superpotato:',
            'reaction' => ':superpotato:',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ];
    }
}
