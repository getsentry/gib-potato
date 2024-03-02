<?php
declare(strict_types=1);

namespace App\Service;

use App\Event\MessageEvent;
use App\Event\ReactionAddedEvent;
use App\Model\Entity\User;
use Cake\ORM\Locator\LocatorAwareTrait;
use Sentry\Metrics\MetricsUnit;
use function Sentry\metrics;

class AwardService
{
    use LocatorAwareTrait;

    /**
     * @param \App\Model\Entity\User $fromUser User who did gib the potato.
     * @param array<\App\Model\Entity\User> $toUsers Users who will receive the potato.
     * @param \App\Event\MessageEvent|\App\Event\ReactionAddedEvent $event The event.
     * @return void
     */
    public function gib(
        User $fromUser,
        array $toUsers,
        MessageEvent|ReactionAddedEvent $event,
    ): void {
        foreach ($toUsers as $toUser) {
            $this->gibToUser(
                fromUser: $fromUser,
                toUser: $toUser,
                event: $event,
            );
        }
    }

    /**
     * @param \App\Model\Entity\User $fromUser User who did gib the potato.
     * @param \App\Model\Entity\User $toUser User who will receive the potato.
     * @param \App\Event\MessageEvent|\App\Event\ReactionAddedEvent $event The event.
     * @return string created message id
     */
    private function gibToUser(
        User $fromUser,
        User $toUser,
        MessageEvent|ReactionAddedEvent $event,
    ): void {
        $messagesTable = $this->fetchTable('Messages');

        $message = $messagesTable->newEntity([
            'sender_user_id' => $fromUser->id,
            'receiver_user_id' => $toUser->id,
            'amount' => $event->amount,
            'type' => str_replace(':', '', $event->reaction),
        ], [
            'accessibleFields' => [
                'sender_user_id' => true,
                'receiver_user_id' => true,
                'amount' => true,
                'type' => true,
            ],
        ]);
        $messagesTable->saveOrFail($message);
         
        metrics()->increment(
            key: 'gibpotato.potatoes.given_out',
            value: $event->amount,
            unit: MetricsUnit::custom('potato'),
        );

        return $message->id;
    }
}
