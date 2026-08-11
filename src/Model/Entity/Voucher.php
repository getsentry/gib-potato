<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Voucher Entity
 *
 * @property string $id
 * @property string $sender_user_id
 * @property string $receiver_user_id
 * @property string $channel
 * @property string $timestamp
 * @property string $permalink
 * @property string $status
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\User $sender_user
 * @property \App\Model\Entity\User $receiver_user
 */
class Voucher extends Entity
{
    protected array $_accessible = [
        '*' => false,
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_REDEEMED = 'redeemed';

    public const MAX_AMOUNT = 5;
}
