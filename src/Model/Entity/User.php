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
 * @property boolean $slack_is_bot
 * @property array|null $notifications
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
        '*' => false,
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DELETED = 'deleted';


    protected function _getNotifications($notifications): array
    {
        if (empty($notifications)) {
            return [
                'sent' => true,
                'received' => true,
            ];
        }

        return $notifications;
    }
}
