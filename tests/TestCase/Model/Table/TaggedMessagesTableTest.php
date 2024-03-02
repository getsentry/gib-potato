<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TaggedMessagesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\TaggedMessagesTable Test Case
 */
class TaggedMessagesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\TaggedMessagesTable
     */
    protected $TaggedMessages;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.TaggedMessages',
        'app.Tags',
        'app.Messages',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('TaggedMessages') ? [] : ['className' => TaggedMessagesTable::class];
        $this->TaggedMessages = $this->getTableLocator()->get('TaggedMessages', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->TaggedMessages);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\TaggedMessagesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
