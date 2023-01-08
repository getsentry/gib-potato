<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property string $id
 * @property string $status
 * @property string $slack_user_id
 * @property string $slack_name
 * @property string $slack_picture
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class User extends Entity
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
        'status' => true,
        'slack_user_id' => true,
        'slack_name' => true,
        'slack_picture' => true,
        'created' => true,
        'modified' => true,
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DELETED = 'deleted';
}
