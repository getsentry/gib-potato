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
 * @property int $progression_id
 * @property string $status
 * @property string $role
 * @property string $slack_user_id
 * @property string $slack_name
 * @property string $slack_picture
 * @property bool $slack_is_bot
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

    public const ROLE_ROOT = 'root';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';
    public const ROLE_SERVICE = 'service';

    /**
     * @param array<string, bool>|null $notifications The user's notification settings.
     * @return array<string, bool>
     */
    protected function _getNotifications(?array $notifications = []): array
    {
        if (empty($notifications)) {
            return [
                'sent' => true,
                'received' => true,
            ];
        }

        return $notifications;
    }

    /**
     * @return int
     */
    public function potatoSent(): int
    {
        $messagesTable = $this->fetchTable('Messages');

        $query = $messagesTable->find();
        $result = $query
            ->select([
                'sent' => $query->func()->sum('amount'),
            ])
            ->where([
                'sender_user_id' => $this->id,
                'type' => Message::TYPE_POTATO,
            ])
            ->first();

        return (int)$result->sent;
    }

    /**
     * @return int
     */
    public function potatoReceived(): int
    {
        $messagesTable = $this->fetchTable('Messages');

        $query = $messagesTable->find();
        $result = $query
            ->select([
                'received' => $query->func()->sum('amount'),
            ])
            ->where([
                'receiver_user_id' => $this->id,
                'type' => Message::TYPE_POTATO,
            ])
            ->first();

        return (int)$result->received;
    }

    /**
     * @return int
     */
    public function potatoSentToday(): int
    {
        $messagesTable = $this->fetchTable('Messages');

        $query = $messagesTable->find();
        $result = $query
            ->select([
                'sent' => $query->func()->sum('amount'),
            ])
            ->where([
                'sender_user_id' => $this->id,
                'type' => Message::TYPE_POTATO,
                'DATE(created)' => $query->func()->now('date'),
            ])
            ->first();

        return (int)$result->sent;
    }

    /**
     * @return int
     */
    public function potatoReceivedToday(): int
    {
        $messagesTable = $this->fetchTable('Messages');

        $query = $messagesTable->find();
        $result = $query
            ->select([
                'received' => $query->func()->sum('amount'),
            ])
            ->where([
                'receiver_user_id' => $this->id,
                'type' => Message::TYPE_POTATO,
                'DATE(created)' => $query->func()->now('date'),
            ])
            ->first();

        return (int)$result->received;
    }

    /**
     * @return int
     */
    public function potatoLeftToday(): int
    {
        $messagesTable = $this->fetchTable('Messages');

        $query = $messagesTable->find();
        $result = $query
            ->select([
                'sent' => $query->func()->sum('amount'),
            ])
            ->where([
                'sender_user_id' => $this->id,
                'type' => Message::TYPE_POTATO,
                'DATE(created)' => $query->func()->now('date'),
            ])
            ->first();

        return Message::MAX_AMOUNT - (int)$result->sent;
    }

    /**
     * @return string
     */
    public function potatoResetInHours(): string
    {
        $time = new FrozenTime();
        $hours = 23 - (int)$time->i18nFormat('HH');

        return (string)$hours;
    }

    /**
     * @return string
     */
    public function potatoResetInMinutes(): string
    {
        $time = new FrozenTime();
        $minutes = 59 - (int)$time->i18nFormat('mm');

        return (string)$minutes;
    }

    /**
     * @return int
     */
    public function spendablePotato(): int
    {
        $pruchasesTable = $this->fetchTable('Purchases');

        $query = $pruchasesTable->find();
        $result = $query
            ->select([
                'spent' => $query->func()->sum('price'),
            ])
            ->where([
                'user_id' => $this->id,
            ])
            ->first();

        return $this->potatoReceived() - (int)$result->spent;
    }
}
