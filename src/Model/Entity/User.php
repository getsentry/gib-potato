<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;
use Cake\ORM\Locator\LocatorAwareTrait;

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
    use LocatorAwareTrait;

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

    public function potatoSent(): int
    {
        $messagesTable = $this->fetchTable('Messages');

        $query = $messagesTable->find();
        $result = $query
            ->select([
                'sent' => $query->func()->sum('amount')
            ])
            ->where([
                'sender_user_id' => $this->id,
                'type' => Message::TYPE_POTATO,
            ])
            ->first();

        return (int) $result->sent;
    }

    public function potatoReceived(): int
    {
        $messagesTable = $this->fetchTable('Messages');

        $query = $messagesTable->find();
        $result = $query
            ->select([
                'received' => $query->func()->sum('amount')
            ])
            ->where([
                'receiver_user_id' => $this->id,
                'type' => Message::TYPE_POTATO,
            ])
            ->first();

        return (int) $result->received;
    }

    public function potatoSentToday(): int
    {
        $messagesTable = $this->fetchTable('Messages');

        $query = $messagesTable->find();
        $result = $query
            ->select([
                'sent' => $query->func()->sum('amount')
            ])
            ->where([
                'sender_user_id' => $this->id,
                'type' => Message::TYPE_POTATO,
                'created >=' => new FrozenTime('24 hours ago'),
            ])
            ->first();

        return (int) $result->sent;
    }

    public function potatoReceivedToday(): int
    {
        $messagesTable = $this->fetchTable('Messages');

        $query = $messagesTable->find();
        $result = $query
            ->select([
                'received' => $query->func()->sum('amount')
            ])
            ->where([
                'receiver_user_id' => $this->id,
                'type' => Message::TYPE_POTATO,
                'created >=' => new FrozenTime('24 hours ago'),
            ])
            ->first();

        return (int) $result->received;
    }

    public function potatoLeftToday(): int
    {
        $messagesTable = $this->fetchTable('Messages');

        $query = $messagesTable->find();
        $result = $query
            ->select([
                'sent' => $query->func()->sum('amount')
            ])
            ->where([
                'sender_user_id' => $this->id,
                'type' => Message::TYPE_POTATO,
                'created >=' => new FrozenTime('24 hours ago'),
            ])
            ->first();

        return Message::MAX_AMOUNT - (int) $result->sent;
    }
}
