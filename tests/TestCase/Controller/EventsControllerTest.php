<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\EventsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\EventsController Test Case
 *
 * @uses \App\Controller\EventsController
 */
class EventsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Messages',
        'app.Users',
    ];

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\EventsController::index()
     */
    public function testIndex(): void
    {
        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
        ]);
        $this->post('/events', json_encode([
            'type' => 'message',
            'amount' => 1,
            'sender' => 'U001',
            'receivers' => [
                'U002',
            ],
            'channel' => 'C001',
            'text' => '<@U002> :potato:',
            'timestamp' => '1672531200',
            'event_timestamp' => '1672531200',
            'permalink' => 'https://example.com/permalink',
        ]));
        $this->assertResponseOk();
    }
}
