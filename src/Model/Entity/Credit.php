<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Credit Entity
 *
 * @property int $id
 * @property string $user_id
 * @property int $amount
 * @property \Cake\I18n\DateTime|null $created
 *
 * @property \App\Model\Entity\User $user
 */
class Credit extends Entity
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
        'user_id' => true,
        'amount' => true,
        'created' => true,
    ];
}
