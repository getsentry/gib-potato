<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Message;
use App\Model\Entity\User;
use Cake\Chronos\Chronos;
use Cake\I18n\DateTime;
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
     * @var \App\Model\Entity\User
     */
    protected $UserBuyer;

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
        $this->UserBuyer = $usersTable->get('00000000-0000-0000-0000-000000000004');
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
        unset($this->UserBuyer);

        Chronos::setTestNow();

        parent::tearDown();
    }

    /**
     * Test getStartOfDay method
     *
     * @return void
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
     */
    public function testPotatoResetInHours(): void
    {
        Chronos::setTestNow(new Chronos('2024-07-17 12:00:00', 'UTC'));

        $this->assertSame('9', $this->UserEurope->potatoResetInHours());
        $this->assertSame('15', $this->UserCanada->potatoResetInHours());
        $this->assertSame('18', $this->UserUS->potatoResetInHours());
    }

    /**
     * Test spendablePotato method with no purchases
     *
     * @return void
     */
    public function testSpendablePotatoWithNoPurchases(): void
    {
        Chronos::setTestNow(new Chronos('2024-07-17 12:00:00', 'UTC'));

        $this->addReceivedPotatoes($this->UserBuyer, 100);

        $this->assertSame(100, $this->UserBuyer->spendablePotato());
    }

    /**
     * Test spendablePotato method returns zero when spending limit reached
     *
     * @return void
     */
    public function testSpendablePotatoReturnsZeroWhenLimitReached(): void
    {
        Chronos::setTestNow(new Chronos('2024-07-17 12:00:00', 'UTC'));

        $this->addReceivedPotatoes($this->UserBuyer, 600);
        $this->addPurchase($this->UserBuyer, 500, DateTime::now('UTC')->subDays(30));

        $this->assertSame(0, $this->UserBuyer->spendablePotato());
    }

    /**
     * Test spendablePotato method counts purchase at exactly 90 days
     *
     * @return void
     */
    public function testSpendablePotatoCountsPurchaseAtExactly90Days(): void
    {
        Chronos::setTestNow(new Chronos('2024-07-17 12:00:00', 'UTC'));

        $this->addReceivedPotatoes($this->UserBuyer, 600);
        $this->addPurchase($this->UserBuyer, 500, DateTime::now('UTC')->subDays(90));

        $this->assertSame(0, $this->UserBuyer->spendablePotato());
    }

    /**
     * Test spendablePotato method ignores purchases older than 90 days
     *
     * @return void
     */
    public function testSpendablePotatoIgnoresPurchasesOlderThan90Days(): void
    {
        Chronos::setTestNow(new Chronos('2024-07-17 12:00:00', 'UTC'));

        $this->addReceivedPotatoes($this->UserBuyer, 600);
        $this->addPurchase($this->UserBuyer, 500, DateTime::now('UTC')->subDays(91));

        $this->assertSame(600, $this->UserBuyer->spendablePotato());
    }

    /**
     * Test spendablePotato method counts recent and ignores old purchases
     *
     * @return void
     */
    public function testSpendablePotatoCountsRecentAndIgnoresOldPurchases(): void
    {
        Chronos::setTestNow(new Chronos('2024-07-17 12:00:00', 'UTC'));

        $this->addReceivedPotatoes($this->UserBuyer, 600);
        $this->addPurchase($this->UserBuyer, 400, DateTime::now('UTC')->subDays(91));
        $this->addPurchase($this->UserBuyer, 200, DateTime::now('UTC')->subDays(10));

        $this->assertSame(400, $this->UserBuyer->spendablePotato());
    }

    /**
     * Test spendablePotato method returns zero when multiple purchases exceed limit
     *
     * @return void
     */
    public function testSpendablePotatoReturnsZeroWhenMultiplePurchasesExceedLimit(): void
    {
        Chronos::setTestNow(new Chronos('2024-07-17 12:00:00', 'UTC'));

        $this->addReceivedPotatoes($this->UserBuyer, 700);
        $this->addPurchase($this->UserBuyer, 300, DateTime::now('UTC')->subDays(30));
        $this->addPurchase($this->UserBuyer, 300, DateTime::now('UTC')->subDays(10));

        $this->assertSame(0, $this->UserBuyer->spendablePotato());
    }

    /**
     * Test spendablePotato method clamps to zero when spent exceeds received
     *
     * @return void
     */
    public function testSpendablePotatoClampsToZeroWhenSpentExceedsReceived(): void
    {
        Chronos::setTestNow(new Chronos('2024-07-17 12:00:00', 'UTC'));

        $this->addReceivedPotatoes($this->UserBuyer, 50);
        $this->addPurchase($this->UserBuyer, 200, DateTime::now('UTC')->subDays(30));

        $this->assertSame(0, $this->UserBuyer->spendablePotato());
    }

    private function addReceivedPotatoes(User $user, int $amount): void
    {
        $messagesTable = $this->fetchTable('Messages');
        $message = $messagesTable->newEntity([
            'sender_user_id' => $user->id,
            'receiver_user_id' => $user->id,
            'type' => Message::TYPE_POTATO,
            'amount' => $amount,
        ], ['accessibleFields' => ['*' => true]]);
        $messagesTable->saveOrFail($message);
    }

    private function addPurchase(User $user, int $price, DateTime $created): void
    {
        $purchasesTable = $this->fetchTable('Purchases');
        $purchase = $purchasesTable->newEntity([
            'user_id' => $user->id,
            'name' => 'Test Product',
            'description' => 'Test',
            'image_link' => 'https://example.com/test.jpg',
            'price' => $price,
            'created' => $created,
        ], ['accessibleFields' => ['*' => true]]);
        $purchasesTable->saveOrFail($purchase);
    }
}
