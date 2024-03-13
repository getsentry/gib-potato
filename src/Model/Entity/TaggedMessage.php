<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TaggedMessage Entity
 *
 * @property string $id
 * @property \Cake\I18n\DateTime $created
 * @property string|null $message
 * @property string|null $sender_user_id
 * @property string $permalink
 *
 * @property \App\Model\Entity\User $user
 */
class TaggedMessage extends Entity
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
        '*' => false,
    ];

    public const TAG = '#quickwin';
}
