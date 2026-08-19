<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Poll Entity
 *
 * @property int $id
 * @property string $user_id
 * @property string $title
 * @property string $type
 * @property string $status
 * @property bool $anonymous
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\PollOption[] $poll_options
 */
class Poll extends Entity
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

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    public const TYPE_MULTIPLE = 'multiple';
    public const TYPE_SINGLE = 'single';
}
