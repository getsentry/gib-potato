<?php
declare(strict_types=1);

namespace App\Service;

use App\Event\MessageEvent;
use App\Event\ReactionAddedEvent;
use App\Model\Entity\User;
use App\Model\Entity\TaggedMessage;
use Cake\ORM\Locator\LocatorAwareTrait;
use Sentry\Metrics\MetricsUnit;
use function Sentry\metrics;

class TaggedMessageService
{
    use LocatorAwareTrait;

    /**
     * @param \App\Model\Entity\User $fromUser User who did gib the potato.
     * @param array<\App\Model\Entity\User> $toUsers Users who will receive the potato.
     * @param \App\Event\MessageEvent|\App\Event\ReactionAddedEvent $event The event.
     * @return void
     */
    public function storeMessageIfTagged(
        User $fromUser,
        MessageEvent|ReactionAddedEvent $event,
    ): void {
        if (!str_contains($event->text, TaggedMessage::TAG)) {
            return;
        }
        
        $taggedMessagesTable = $this->fetchTable('TaggedMessages');

        $message = $taggedMessagesTable->newEntity([
            'sender_user_id' => $fromUser->id,
            'message' => $event->text,
        ], [
            'accessibleFields' => [
                'sender_user_id' => true,
                'message' => true,
            ],
        ]);
        $taggedMessagesTable->saveOrFail($message);

        metrics()->increment(
            key: 'gibpotato.message.tagged',
            value: 1,
            unit: MetricsUnit::custom('tags'),
        );
    }
}
