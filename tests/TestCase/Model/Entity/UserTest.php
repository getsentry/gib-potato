<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Message;
use Cake\Chronos\Chronos;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;

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
        'app.Users',
        'app.Messages',
        'app.Purchases',
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
     * Test getStartOfDay method
     *
     * @return void
     * @uses \App\Model\Entity\User::getStartOfDay()
     */
    public function testGetStartOfDay(): void
    {
        Chronos::setTestNow(new Chronos('2024-07-17 12:00:00', 'UTC'));

        $startOfDay = $this->UserEurope->getStartOfDay();
        $this->assertSame('2024-07-16 22:00:00', $startOfDay->toDateTimeString());

        $startOfDay = $this->UserCanada->getStartOfDay();
        $this->assertSame('2024-07-17 04:00:00', $startOfDay->toDateTimeString());

        $startOfDay = $this->UserUS->getStartOfDay();
        $this->assertSame('2024-07-17 07:00:00', $startOfDay->toDateTimeString());
    }

    /**
     * Test getEndOfDay method
     *
     * @return void
     * @uses \App\Model\Entity\User::getEndOfDay()
     */
    public function testGetEndOfDay(): void
    {
        Chronos::setTestNow(new Chronos('2024-07-17 12:00:00', 'UTC'));

        $endOfDay = $this->UserEurope->getEndOfDay();
        $this->assertSame('2024-07-17 21:59:59', $endOfDay->toDateTimeString());

        $endOfDay = $this->UserCanada->getEndOfDay();
        $this->assertSame('2024-07-18 03:59:59', $endOfDay->toDateTimeString());

        $endOfDay = $this->UserUS->getEndOfDay();
        $this->assertSame('2024-07-18 06:59:59', $endOfDay->toDateTimeString());
    }

    /**
     * Test potatoResetInHours method
     *
     * @return void
     * @uses \App\Model\Entity\User::potatoResetInHours()
     */
    public function testPotatoResetInHours(): void
    {
        Chronos::setTestNow(new Chronos('2024-07-17 12:00:00', 'UTC'));

        $this->assertSame('9', $this->UserEurope->potatoResetInHours());
        $this->assertSame('15', $this->UserCanada->potatoResetInHours());
        $this->assertSame('18', $this->UserUS->potatoResetInHours());
    }

    /**
     * Test spendablePotato method with no purchases and no received potatoes
     *
     * @return void
     * @uses \App\Model\Entity\User::spendablePotato()
     */
    public function testSpendablePotatoNoPurchasesNoReceived(): void
    {
        // User 3 has no purchases and no received potatoes
        $spendable = $this->UserUS->spendablePotato();
        $this->assertSame(0, $spendable);
    }

    /**
     * Test spendablePotato method with recent purchases within 90-day limit
     *
     * @return void
     * @uses \App\Model\Entity\User::spendablePotato()
     */
    public function testSpendablePotatoWithRecentPurchases(): void
    {
        Chronos::setTestNow(new Chronos('2025-09-12 12:00:00', 'UTC'));

        // Add some received potatoes for User 1
        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->newEntity([
            'id' => Text::uuid(),
            'sender_user_id' => $this->UserCanada->id,
            'receiver_user_id' => $this->UserEurope->id,
            'amount' => 600,
            'type' => Message::TYPE_POTATO,
            'created' => new Chronos('2025-08-01 10:00:00'),
        ], ['accessibleFields' => ['*' => true]]);
        $messagesTable->saveOrFail($message);

        // User 1 has received 600 potatoes
        // User 1 has spent 100 (>90 days ago) + 200 (30 days ago) + 150 (10 days ago) = 450 total
        // Only 200 + 150 = 350 in last 90 days
        // Available balance: 600 - 450 = 150
        // Remaining 90-day limit: 500 - 350 = 150
        // Should return min(150, 150) = 150
        $spendable = $this->UserEurope->spendablePotato();
        $this->assertSame(150, $spendable);
    }

    /**
     * Test spendablePotato method when user has reached 90-day spending limit
     *
     * @return void
     * @uses \App\Model\Entity\User::spendablePotato()
     */
    public function testSpendablePotatoAtSpendingLimit(): void
    {
        Chronos::setTestNow(new Chronos('2025-09-12 12:00:00', 'UTC'));

        // Add some received potatoes for User 2
        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->newEntity([
            'id' => Text::uuid(),
            'sender_user_id' => $this->UserEurope->id,
            'receiver_user_id' => $this->UserCanada->id,
            'amount' => 800,
            'type' => Message::TYPE_POTATO,
            'created' => new Chronos('2025-08-01 10:00:00'),
        ], ['accessibleFields' => ['*' => true]]);
        $messagesTable->saveOrFail($message);

        // User 2 has received 800 potatoes
        // User 2 has spent 300 (60 days ago) + 200 (20 days ago) = 500 in last 90 days
        // Has reached the 500 limit
        $spendable = $this->UserCanada->spendablePotato();
        $this->assertSame(0, $spendable);
    }

    /**
     * Test spendablePotato method with old purchases outside 90-day window
     *
     * @return void
     * @uses \App\Model\Entity\User::spendablePotato()
     */
    public function testSpendablePotatoWithOldPurchases(): void
    {
        Chronos::setTestNow(new Chronos('2025-09-12 12:00:00', 'UTC'));

        // Create a user with only old purchases
        $purchasesTable = $this->fetchTable('Purchases');
        $purchase = $purchasesTable->newEntity([
            'user_id' => $this->UserUS->id,
            'name' => 'Very Old Purchase',
            'description' => 'Purchase from 100 days ago',
            'image_link' => 'https://example.com/old.jpg',
            'price' => 400,
            'created' => new Chronos('2025-06-04 10:00:00'), // > 90 days ago
        ], ['accessibleFields' => ['*' => true]]);
        $purchasesTable->saveOrFail($purchase);

        // Add received potatoes
        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->newEntity([
            'id' => Text::uuid(),
            'sender_user_id' => $this->UserEurope->id,
            'receiver_user_id' => $this->UserUS->id,
            'amount' => 600,
            'type' => Message::TYPE_POTATO,
            'created' => new Chronos('2025-08-01 10:00:00'),
        ], ['accessibleFields' => ['*' => true]]);
        $messagesTable->saveOrFail($message);

        // User 3 has received 600 potatoes
        // User 3 has spent 400 total, but 0 in last 90 days
        // Available balance: 600 - 400 = 200
        // Remaining 90-day limit: 500 - 0 = 500
        // Should return min(200, 500) = 200
        $spendable = $this->UserUS->spendablePotato();
        $this->assertSame(200, $spendable);
    }

    /**
     * Test spendablePotato method when balance is less than 90-day limit
     *
     * @return void
     * @uses \App\Model\Entity\User::spendablePotato()
     */
    public function testSpendablePotatoLimitedByBalance(): void
    {
        Chronos::setTestNow(new Chronos('2025-09-12 12:00:00', 'UTC'));

        // Create a scenario where available balance is less than 90-day limit
        $purchasesTable = $this->fetchTable('Purchases');
        $purchase = $purchasesTable->newEntity([
            'user_id' => $this->UserUS->id,
            'name' => 'Recent Small Purchase',
            'description' => 'Small purchase from 5 days ago',
            'image_link' => 'https://example.com/small.jpg',
            'price' => 50,
            'created' => new Chronos('2025-09-07 10:00:00'), // 5 days ago
        ], ['accessibleFields' => ['*' => true]]);
        $purchasesTable->saveOrFail($purchase);

        // Add limited received potatoes
        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->newEntity([
            'id' => Text::uuid(),
            'sender_user_id' => $this->UserCanada->id,
            'receiver_user_id' => $this->UserUS->id,
            'amount' => 100,
            'type' => Message::TYPE_POTATO,
            'created' => new Chronos('2025-08-01 10:00:00'),
        ], ['accessibleFields' => ['*' => true]]);
        $messagesTable->saveOrFail($message);

        // User 3 has received 100 potatoes
        // User 3 has spent 50 in last 90 days
        // Available balance: 100 - 50 = 50
        // Remaining 90-day limit: 500 - 50 = 450
        // Should return min(50, 450) = 50 (limited by balance)
        $spendable = $this->UserUS->spendablePotato();
        $this->assertSame(50, $spendable);
    }
}
