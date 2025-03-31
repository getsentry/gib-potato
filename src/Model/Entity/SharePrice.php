<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SharePrice Entity
 *
 * @property int $id
 * @property int|null $stock_id
 * @property \Cake\I18n\DateTime|null $created
 * @property int|null $price
 *
 * @property \App\Model\Entity\Stock $stock
 */
class SharePrice extends Entity
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
        'stock_id' => true,
        'price' => true,
        'created' => true,
    ];
}
