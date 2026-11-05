<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Message Entity
 *
 * @property string $id
 * @property string $sender_user_id
 * @property string $receiver_user_id
 * @property int $amount
 * @property string $type
 * @property \Cake\I18n\DateTime|null $created
 *
 * @property \App\Model\Entity\User $user
 */
class Message extends Entity
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

    public const TYPE_POTATO = 'potato';
    public const TYPE_SUPER_POTATO = 'superpotato';
    public const MAX_AMOUNT = 5;

    /**
     * Unspent daily potatoes needed to unlock one super potato.
     */
    public const SUPER_POTATO_COST = 20;

    /**
     * How many potatoes one super potato is worth to its receiver.
     */
    public const SUPER_POTATO_VALUE = 5;

    /**
     * Only days after this date add unspent potatoes to the super potato bank.
     */
    public const SUPER_POTATO_START = '2026-11-06 00:00:00';

    /**
     * @param string $reaction Reaction name, with or without surrounding colons.
     * @return bool
     */
    public static function isSuperPotato(string $reaction): bool
    {
        return trim($reaction, ':') === self::TYPE_SUPER_POTATO;
    }
}
