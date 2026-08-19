<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\FactoryTrait;
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
        'app.Vouchers',
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

    public function testTypeMessageRejectedDoesNotCreateReceivers(): void
    {
        $this->mockSlackClientPostMessage();
        $this->mockSlackClientPostEphemeral();
        $this->mockSlackClientGetUser('U6666');

        // Use up the sender's allowance so validation rejects the event
        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->newEntity([
            'sender_user_id' => '00000000-0000-0000-0000-000000000001',
            'receiver_user_id' => '00000000-0000-0000-0000-000000000002',
            'amount' => 5,
            'type' => 'potato',
        ], ['accessibleFields' => ['*' => true]]);
        $messagesTable->saveOrFail($message);

        $usersTable = $this->fetchTable('Users');
        $usersBefore = $usersTable->find()->count();

        $this->post('/events', json_encode([
            'type' => 'message',
            'amount' => 1,
            'sender' => 'U1111',
            'receivers' => [
                'U6666',
            ],
            'channel' => 'C1111',
            'text' => '<@U6666> :potato:',
            'reaction' => 'potato',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();

        // A rejected event must not onboard the receiver
        $this->assertSame($usersBefore, $usersTable->find()->count());
        $this->assertSame(1, $messagesTable->find()->count());
    }

    public function testTypeMessageToUserWithUnknownBotFlag(): void
    {
        $this->mockSlackClientPostMessage();

        $this->post('/events', json_encode([
            'type' => 'message',
            'amount' => 1,
            'sender' => 'U1111',
            'receivers' => [
                'U7777',
            ],
            'channel' => 'C1111',
            'text' => '<@U7777> :potato:',
            'reaction' => 'potato',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();

        $messages = $this->fetchTable('Messages')
            ->find()
            ->all();

        $this->assertSame(1, $messages->count());
        $this->assertSame('00000000-0000-0000-0000-000000000006', $messages->first()->receiver_user_id);
    }

    public function testTypeMessageToSelf(): void
    {
        $this->mockSlackClientPostMessage();
        $this->mockSlackClientPostEphemeral();

        $this->post('/events', json_encode([
            'type' => 'message',
            'amount' => 1,
            'sender' => 'U1111',
            'receivers' => [
                'U1111',
            ],
            'channel' => 'C1111',
            'text' => '<@U1111> :potato:',
            'reaction' => 'potato',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();

        $this->assertSame(0, $this->fetchTable('Messages')->find()->count());

        $sender = $this->fetchTable('Users')->get('00000000-0000-0000-0000-000000000001');
        $this->assertSame(5, $sender->potatoLeftToday());
    }

    public function testTypeMessageIgnoresBotReceivers(): void
    {
        $this->mockSlackClientPostMessage();

        $this->post('/events', json_encode([
            'type' => 'message',
            'amount' => 2,
            'sender' => 'U1111',
            'receivers' => [
                'U5555',
                'U2222',
            ],
            'channel' => 'C1111',
            'text' => '<@U5555> <@U2222> :potato: :potato:',
            'reaction' => 'potato',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();

        $messages = $this->fetchTable('Messages')
            ->find()
            ->all();

        $this->assertSame(1, $messages->count());
        $this->assertSame('00000000-0000-0000-0000-000000000002', $messages->first()->receiver_user_id);
        $this->assertSame(2, $messages->first()->amount);

        // The bot's share must not be charged to the sender
        $sender = $this->fetchTable('Users')->get('00000000-0000-0000-0000-000000000001');
        $this->assertSame(2, $sender->potatoSentToday());
        $this->assertSame(3, $sender->potatoLeftToday());
    }

    public function testTypeMessageWithOnlyBotReceivers(): void
    {
        $this->mockSlackClientPostMessage();
        $this->mockSlackClientPostEphemeral();

        $this->post('/events', json_encode([
            'type' => 'message',
            'amount' => 1,
            'sender' => 'U1111',
            'receivers' => [
                'U5555',
            ],
            'channel' => 'C1111',
            'text' => '<@U5555> :potato:',
            'reaction' => 'potato',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();

        $messages = $this->fetchTable('Messages')->find()->all();
        $this->assertSame(0, $messages->count());

        $sender = $this->fetchTable('Users')->get('00000000-0000-0000-0000-000000000001');
        $this->assertSame(5, $sender->potatoLeftToday());
    }

    public function testTypeMessageDoesNotCreateUserForBotReceiver(): void
    {
        $this->mockSlackClientPostMessage();
        $this->mockSlackClientPostEphemeral();
        $this->mockSlackClientGetUser('U6666', isBot: true);

        $usersTable = $this->fetchTable('Users');
        $usersBefore = $usersTable->find()->count();

        $this->post('/events', json_encode([
            'type' => 'message',
            'amount' => 1,
            'sender' => 'U1111',
            'receivers' => [
                'U6666',
            ],
            'channel' => 'C1111',
            'text' => '<@U6666> :potato:',
            'reaction' => 'potato',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();

        $this->assertSame($usersBefore, $usersTable->find()->count());
        $this->assertSame(0, $this->fetchTable('Messages')->find()->count());
    }

    public function testTypeReactionAddedIgnoresBotReceivers(): void
    {
        $this->mockSlackClientPostMessage();

        $this->post('/events', json_encode([
            'type' => 'reaction_added',
            'amount' => 1,
            'sender' => 'U1111',
            'receivers' => [
                'U5555',
                'U2222',
            ],
            'channel' => 'C1111',
            'text' => '<@U5555> <@U2222> :potato:',
            'reaction' => 'potato',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();

        $messages = $this->fetchTable('Messages')
            ->find()
            ->all();

        $this->assertSame(1, $messages->count());
        $this->assertSame('00000000-0000-0000-0000-000000000002', $messages->first()->receiver_user_id);

        $sender = $this->fetchTable('Users')->get('00000000-0000-0000-0000-000000000001');
        $this->assertSame(4, $sender->potatoLeftToday());
    }

    public function testTypeReactionAddedVoucherIgnoresBotReceivers(): void
    {
        $this->mockSlackClientPostMessage();

        $this->post('/events', json_encode([
            'type' => 'reaction_added',
            'amount' => 1,
            'sender' => 'U1111',
            'receivers' => [
                'U5555',
                'U2222',
            ],
            'channel' => 'C1111',
            'text' => '<@U5555> <@U2222> hello',
            'reaction' => ':admission_tickets:',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();

        $vouchers = $this->fetchTable('Vouchers')
            ->find()
            ->all();

        $this->assertSame(1, $vouchers->count());
        $this->assertSame('00000000-0000-0000-0000-000000000002', $vouchers->first()->receiver_user_id);

        $sender = $this->fetchTable('Users')->get('00000000-0000-0000-0000-000000000001');
        $this->assertSame(4, $sender->vouchersLeftToday());
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

    public function testTypeReactionAddedVoucher(): void
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
            'text' => '<@U2222> hello',
            'reaction' => ':admission_tickets:',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();
        $this->assertHeader('Content-Type', 'application/json');

        $vouchers = $this->fetchTable('Vouchers')
            ->find()
            ->all();

        $this->assertSame(1, $vouchers->count());
        $this->assertSame('00000000-0000-0000-0000-000000000001', $vouchers->first()->sender_user_id);
        $this->assertSame('00000000-0000-0000-0000-000000000002', $vouchers->first()->receiver_user_id);
        $this->assertSame('pending', $vouchers->first()->status);
        $this->assertSame('C1111', $vouchers->first()->channel);
        $this->assertSame('1672531200', $vouchers->first()->timestamp);
        $this->assertSame('https://example.com/permalink', $vouchers->first()->permalink);

        $messages = $this->fetchTable('Messages')
            ->find()
            ->all();

        $this->assertSame(0, $messages->count());
    }

    public function testTypeReactionAddedVoucherDoesNotCreateMessageRecord(): void
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
            'text' => '<@U2222> hello',
            'reaction' => ':admission_tickets:',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();

        $messages = $this->fetchTable('Messages')->find()->all();
        $this->assertSame(0, $messages->count());
    }

    public function testTypeReactionAddedVoucherToSelf(): void
    {
        $this->mockSlackClientPostMessage();
        $this->mockSlackClientPostEphemeral();

        $this->post('/events', json_encode([
            'type' => 'reaction_added',
            'amount' => 1,
            'sender' => 'U1111',
            'receivers' => [
                'U1111',
            ],
            'channel' => 'C1111',
            'text' => '<@U1111> hello',
            'reaction' => ':admission_tickets:',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();

        $vouchers = $this->fetchTable('Vouchers')->find()->all();
        $this->assertSame(0, $vouchers->count());
    }

    public function testTypeReactionAddedVoucherDailyLimit(): void
    {
        $this->mockSlackClientPostMessage();
        $this->mockSlackClientPostEphemeral();

        $vouchersTable = $this->fetchTable('Vouchers');
        for ($i = 0; $i < 5; $i++) {
            $voucher = $vouchersTable->newEntity([
                'sender_user_id' => '00000000-0000-0000-0000-000000000001',
                'receiver_user_id' => '00000000-0000-0000-0000-000000000002',
                'channel' => 'C1111',
                'timestamp' => '1672531200',
                'permalink' => 'https://example.com/permalink',
                'status' => 'pending',
            ], ['accessibleFields' => ['*' => true]]);
            $vouchersTable->saveOrFail($voucher);
        }

        $this->post('/events', json_encode([
            'type' => 'reaction_added',
            'amount' => 1,
            'sender' => 'U1111',
            'receivers' => [
                'U2222',
            ],
            'channel' => 'C1111',
            'text' => '<@U2222> hello',
            'reaction' => ':admission_tickets:',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));

        $this->assertResponseOk();

        $vouchers = $vouchersTable->find()->all();
        $this->assertSame(5, $vouchers->count());
    }

    public function testTypeReactionRemovedVoucher(): void
    {
        $vouchersTable = $this->fetchTable('Vouchers');
        $voucher = $vouchersTable->newEntity([
            'sender_user_id' => '00000000-0000-0000-0000-000000000001',
            'receiver_user_id' => '00000000-0000-0000-0000-000000000002',
            'channel' => 'C1111',
            'timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
            'status' => 'pending',
        ], ['accessibleFields' => ['*' => true]]);
        $vouchersTable->saveOrFail($voucher);

        $this->post('/events', json_encode([
            'type' => 'reaction_removed',
            'sender' => 'U1111',
            'channel' => 'C1111',
            'reaction' => ':admission_tickets:',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
        ]));

        $this->assertResponseOk();
        $this->assertHeader('Content-Type', 'application/json');

        $vouchers = $vouchersTable->find()->all();
        $this->assertSame(0, $vouchers->count());
    }

    public function testTypeReactionRemovedVoucherDoesNotDeleteRedeemed(): void
    {
        $vouchersTable = $this->fetchTable('Vouchers');
        $voucher = $vouchersTable->newEntity([
            'sender_user_id' => '00000000-0000-0000-0000-000000000001',
            'receiver_user_id' => '00000000-0000-0000-0000-000000000002',
            'channel' => 'C1111',
            'timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
            'status' => 'redeemed',
        ], ['accessibleFields' => ['*' => true]]);
        $vouchersTable->saveOrFail($voucher);

        $this->post('/events', json_encode([
            'type' => 'reaction_removed',
            'sender' => 'U1111',
            'channel' => 'C1111',
            'reaction' => ':admission_tickets:',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
        ]));

        $this->assertResponseOk();

        $vouchers = $vouchersTable->find()->all();
        $this->assertSame(1, $vouchers->count());
        $this->assertSame('redeemed', $vouchers->first()->status);
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
