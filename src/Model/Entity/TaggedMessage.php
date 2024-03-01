<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TaggedMessage Entity
 *
 * @property string $id
 * @property string $tag_id
 * @property string $message_id
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\Message $message
 * @property \App\Model\Entity\Tag $tag
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
        'tag_id' => true,
        'message_id' => true,
        'created' => true,
        'message' => true,
        'tag' => true,
    ];
}
