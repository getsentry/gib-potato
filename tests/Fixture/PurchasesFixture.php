<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PurchasesFixture
 */
class PurchasesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array<string, mixed>>
     */
    public array $records = [
        // User 1 purchases
        [
            'id' => 1,
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'presentee_id' => null,
            'name' => 'Old Purchase',
            'description' => 'Purchase from 120 days ago',
            'image_link' => 'https://example.com/old.jpg',
            'message' => null,
            'price' => 100,
            'code' => null,
            'created' => '2025-05-15 10:00:00', // ~120 days ago from Sept 12, 2025
        ],
        [
            'id' => 2,
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'presentee_id' => null,
            'name' => 'Recent Purchase 1',
            'description' => 'Purchase from 30 days ago',
            'image_link' => 'https://example.com/recent1.jpg',
            'message' => null,
            'price' => 200,
            'code' => null,
            'created' => '2025-08-13 10:00:00', // ~30 days ago
        ],
        [
            'id' => 3,
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'presentee_id' => null,
            'name' => 'Recent Purchase 2',
            'description' => 'Purchase from 10 days ago',
            'image_link' => 'https://example.com/recent2.jpg',
            'message' => null,
            'price' => 150,
            'code' => null,
            'created' => '2025-09-02 10:00:00', // ~10 days ago
        ],
        // User 2 purchases (at spending limit)
        [
            'id' => 4,
            'user_id' => '00000000-0000-0000-0000-000000000002',
            'presentee_id' => null,
            'name' => 'Max Purchase 1',
            'description' => 'Purchase from 60 days ago',
            'image_link' => 'https://example.com/max1.jpg',
            'message' => null,
            'price' => 300,
            'code' => null,
            'created' => '2025-07-14 10:00:00', // ~60 days ago
        ],
        [
            'id' => 5,
            'user_id' => '00000000-0000-0000-0000-000000000002',
            'presentee_id' => null,
            'name' => 'Max Purchase 2',
            'description' => 'Purchase from 20 days ago',
            'image_link' => 'https://example.com/max2.jpg',
            'message' => null,
            'price' => 200,
            'code' => null,
            'created' => '2025-08-23 10:00:00', // ~20 days ago
        ],
        // User 3 has no purchases
    ];
}
