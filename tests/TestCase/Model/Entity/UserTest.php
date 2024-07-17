<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use Cake\Chronos\Chronos;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Entity\User Test Case
 */
class UserTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Messages',
        'app.Users',
    ];

    /**
     * @var \App\Model\Entity\User
     */
    protected $UserEurope;

    /**
     * @var \App\Model\Entity\User
     */
    protected $UserCanada;

    /**
     * @var \App\Model\Entity\User
     */
    protected $UserUS;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $usersTable = $this->fetchTable('Users');

        $this->UserEurope = $usersTable->get('00000000-0000-0000-0000-000000000001');
        $this->UserCanada = $usersTable->get('00000000-0000-0000-0000-000000000002');
        $this->UserUS = $usersTable->get('00000000-0000-0000-0000-000000000003');

        Chronos::setTestNow(new Chronos('2024-07-17 12:00:00', 'UTC'));

        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->newEntity([
            'sender_user_id' => $this->UserEurope->id,
            'receiver_user_id' => $this->UserCanada->id,
            'amount' => 5,
            'type' => 'potato',
        ], [
            'accessibleFields' => [
                'sender_user_id' => true,
                'receiver_user_id' => true,
                'amount' => true,
                'type' => true,
            ],
        ]);
        $messagesTable->saveOrFail($message);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->UserEurope);
        unset($this->UserCanada);
        unset($this->UserUS);

        Chronos::setTestNow();

        parent::tearDown();
    }

    /**
     * Test potatoSentToday method
     *
     * @return void
     * @uses \App\Model\Entity\User::potatoSentToday()
     */
    public function testPotatoSentToday(): void
    {
        $this->assertSame(5, $this->UserEurope->potatoSentToday());
        $this->assertSame(0, $this->UserCanada->potatoSentToday());
        $this->assertSame(0, $this->UserUS->potatoSentToday());
    }

    /**
     * Test potatoReceivedToday method
     *
     * @return void
     * @uses \App\Model\Entity\User::potatoReceivedToday()
     */
    public function testPotatoReceivedToday(): void
    {
        $this->assertSame(0, $this->UserEurope->potatoReceivedToday());
        $this->assertSame(5, $this->UserCanada->potatoReceivedToday());
        $this->assertSame(0, $this->UserUS->potatoReceivedToday());
    }

    /**
     * Test potatoLeftToday method
     *
     * @return void
     * @uses \App\Model\Entity\User::potatoLeftToday()
     */
    public function testPotatoLeftToday(): void
    {
        $this->assertSame(0, $this->UserEurope->potatoLeftToday());
        $this->assertSame(5, $this->UserCanada->potatoLeftToday());
        $this->assertSame(5, $this->UserUS->potatoLeftToday());
    }

    /**
     * Test potatoResetInHours method
     *
     * @return void
     * @uses \App\Model\Entity\User::potatoResetInHours()
     */
    public function testPotatoResetInHours(): void
    {
        $this->assertSame('9', $this->UserEurope->potatoResetInHours());
        $this->assertSame('15', $this->UserCanada->potatoResetInHours());
        $this->assertSame('18', $this->UserUS->potatoResetInHours());
    }
}
