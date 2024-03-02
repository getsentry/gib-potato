<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TaggedMessagesFixture
 */
class TaggedMessagesFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 'a428fd32-6965-4fc8-8495-42da264c86d8',
                'created' => 1709364434,
                'message' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'sender_user_id' => '9c7fb3a8-8d6f-4e50-b927-9944a39e0841',
                'permalink' => 'Lorem ipsum dolor sit amet',
            ],
        ];
        parent::init();
    }
}
