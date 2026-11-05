<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
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
 * @property string|null $slack_email
 * @property string $slack_picture
 * @property string $slack_time_zone
 * @property bool $slack_is_bot
 * @property int|null $birthday_day
 * @property int|null $birthday_month
 * @property string|null $hub
 * @property array|null $notifications
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
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
    protected array $_accessible = [
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
        $defaults = [
            'sent' => true,
            'received' => true,
            'too_good_to_go' => false,
        ];

        if (empty($notifications)) {
            return $defaults;
        }

        return array_merge($defaults, $notifications);
    }

    /**
     * @param string|null $slackTimeZone
     * @return string
     */
    protected function _getSlackTimeZone(?string $slackTimeZone): string
    {
        return $slackTimeZone ?? 'UTC';
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
                'type IN' => [Message::TYPE_POTATO, Message::TYPE_SUPER_POTATO],
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
                'type IN' => [Message::TYPE_POTATO, Message::TYPE_SUPER_POTATO],
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
                'created >=' => $this->getStartOfDay(),
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
                'created >=' => $this->getStartOfDay(),
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
                'created >=' => $this->getStartOfDay(),
            ])
            ->first();

        return Message::MAX_AMOUNT - (int)$result->sent;
    }

    /**
     * Unspent potatoes from past days that count towards super potatoes.
     *
     * Only days on which the user did gib at least one potato count, so the
     * bank doesn't fill up for people who never use GibPotato.
     *
     * @return int
     */
    public function superPotatoUnspent(): int
    {
        $messagesTable = $this->fetchTable('Messages');

        $query = $messagesTable->find();
        $result = $query
            ->select([
                'active_days' => $query->expr(
                    'COUNT(DISTINCT DATE((Messages.created AT TIME ZONE \'UTC\') AT TIME ZONE :time_zone))',
                ),
                'sent' => $query->func()->sum('amount'),
            ])
            ->where([
                'sender_user_id' => $this->id,
                'type' => Message::TYPE_POTATO,
                'created >=' => new DateTime(Message::SUPER_POTATO_START, 'UTC'),
                'created <' => $this->getStartOfDay(),
            ])
            ->bind(':time_zone', $this->slack_time_zone, 'string')
            ->first();

        return max(0, (int)$result->active_days * Message::MAX_AMOUNT - (int)$result->sent);
    }

    /**
     * @return int
     */
    public function superPotatoSent(): int
    {
        $messagesTable = $this->fetchTable('Messages');

        $query = $messagesTable->find();
        $result = $query
            ->select([
                'sent' => $query->func()->sum('amount'),
            ])
            ->where([
                'sender_user_id' => $this->id,
                'type' => Message::TYPE_SUPER_POTATO,
            ])
            ->first();

        return intdiv((int)$result->sent, Message::SUPER_POTATO_VALUE);
    }

    /**
     * @return int
     */
    public function superPotatoLeft(): int
    {
        $unlocked = intdiv($this->superPotatoUnspent(), Message::SUPER_POTATO_COST);

        return max(0, $unlocked - $this->superPotatoSent());
    }

    /**
     * Unspent potatoes collected towards the next super potato.
     *
     * @return int
     */
    public function superPotatoProgress(): int
    {
        return $this->superPotatoUnspent() % Message::SUPER_POTATO_COST;
    }

    /**
     * @return string
     */
    public function potatoResetInHours(): string
    {
        $userTime = DateTime::now($this->slack_time_zone);
        $userEndOfDay = $userTime->endOfDay();

        return (string)$userTime->diff($userEndOfDay)->h;
    }

    /**
     * @return string
     */
    public function potatoResetInMinutes(): string
    {
        $userTime = DateTime::now($this->slack_time_zone);
        $userEndOfDay = $userTime->endOfDay();

        return (string)$userTime->diff($userEndOfDay)->i;
    }

    /**
     * @return int
     */
    public function spendablePotato(): int
    {
        $purchasesTable = $this->fetchTable('Purchases');

        $recentQuery = $purchasesTable->find();
        $recentResult = $recentQuery
            ->select([
                'spent' => $recentQuery->func()->sum('price'),
            ])
            ->where([
                'user_id' => $this->id,
                'created >=' => DateTime::now('UTC')->subDays(90),
            ])
            ->first();

        $totalQuery = $purchasesTable->find();
        $totalResult = $totalQuery
            ->select([
                'spent' => $totalQuery->func()->sum('price'),
            ])
            ->where([
                'user_id' => $this->id,
            ])
            ->first();

        $recentSpent = (int)$recentResult->spent;
        $totalSpent = (int)$totalResult->spent;
        $remainingQuota = 500 - $recentSpent;
        $remainingBalance = $this->potatoReceived() - $totalSpent;

        return max(0, min($remainingQuota, $remainingBalance));
    }

    /**
     * @return \Cake\I18n\DateTime
     */
    public function getStartOfDay(): DateTime
    {
        $userTime = DateTime::now($this->slack_time_zone);
        $utcTime = DateTime::now('UTC');

        $utcOffset = $userTime->getOffset($utcTime);

        $startOfDayUser = $userTime->startOfDay()->subSeconds($utcOffset);

        return $startOfDayUser;
    }

    /**
     * @return \Cake\I18n\DateTime
     */
    public function getEndOfDay(): DateTime
    {
        $userTime = DateTime::now($this->slack_time_zone);
        $utcTime = DateTime::now('UTC');

        $utcOffset = $userTime->getOffset($utcTime);

        $endOfDayUser = $userTime->endOfDay()->subSeconds($utcOffset);

        return $endOfDayUser;
    }
}
