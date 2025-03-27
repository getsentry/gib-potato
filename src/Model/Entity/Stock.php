<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Stock Entity
 *
 * @property int $id
 * @property string $symbol
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $description
 *
 * @property \App\Model\Entity\Share[] $shares
 * @property \App\Model\Entity\SharePrice[] $share_prices
 */
class Stock extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'symbol' => true,
        'created' => true,
        'description' => true,
        'shares' => true,
        'share_prices' => true,
    ];
}
