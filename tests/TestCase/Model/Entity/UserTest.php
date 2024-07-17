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
}
