<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Trade Entity
 *
 * @property int $id
 * @property string|null $user_id
 * @property int $share_id
 * @property int $price
 * @property string $status
 * @property string $type
 * @property \Cake\I18n\DateTime|null $created
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Share $share
 */
class Trade extends Entity
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_EXECUTED = 'executed';
    public const STATUS_FAILED = 'failed';

    public const TYPE_BUY = 'buy';
    public const TYPE_SELL = 'sell';

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
        'share_id' => true,
        'price' => true,
        'status' => true,
        'type' => true,
        'created' => true,
        'user' => true,
        'share' => true,
    ];
}
