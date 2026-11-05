<?php
declare(strict_types=1);

namespace App\Service;

use App\Event\MessageEvent;
use App\Event\ReactionAddedEvent;
use App\Model\Entity\Message;
use App\Model\Entity\User;
use Cake\ORM\Locator\LocatorAwareTrait;
use Sentry\SentrySdk;
use function Sentry\logger;
use function Sentry\trace_metrics;

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

        $isSuperPotato = Message::isSuperPotato($event->reaction);
        // Super potatoes are stored with their potato value, so totals, the leaderboard and the shop count them
        $amount = $isSuperPotato ? $event->amount * Message::SUPER_POTATO_VALUE : $event->amount;

        $message = $messagesTable->newEntity([
            'sender_user_id' => $fromUser->id,
            'receiver_user_id' => $toUser->id,
            'amount' => $amount,
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

        $metric = $isSuperPotato ? 'gibpotato.super_potatoes.given_out' : 'gibpotato.potatoes.given_out';

        $span = SentrySdk::getCurrentHub()->getSpan();
        if ($span !== null) {
            $span->setData([
                $metric => $event->amount,
                'gibpotato.event_type' => $event->type,
            ]);
        }
        trace_metrics()->count(
            $metric,
            (float)$event->amount,
            [
                'gibpotato.event_type' => $event->type,
            ],
        );

        logger()->info(
            message: '"%s" gave "%s" %s %s',
            values: [
                $fromUser->slack_name,
                $toUser->slack_name,
                $event->amount,
                $isSuperPotato ? 'super 🥔' : '🥔',
            ],
        );
    }
}
