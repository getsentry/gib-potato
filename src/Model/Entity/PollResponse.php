<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * PollResponse Entity
 *
 * @property int $id
 * @property int $poll_option_id
 * @property string $user_id
 * @property \Cake\I18n\FrozenTime|null $created
 *
 * @property \App\Model\Entity\PollOption $poll_option
 * @property \App\Model\Entity\User $user
 */
class PollResponse extends Entity
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
    protected $_accessible = [
        '*' => false,
    ];
}
