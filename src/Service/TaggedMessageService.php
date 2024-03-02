<?php
declare(strict_types=1);

namespace App\Service;

use App\Event\MessageEvent;
use App\Model\Entity\TaggedMessage;
use App\Model\Entity\User;
use Cake\ORM\Locator\LocatorAwareTrait;
use Sentry\Metrics\MetricsUnit;
use function Sentry\metrics;

class TaggedMessageService
{
    use LocatorAwareTrait;

    /**
     * @param \App\Model\Entity\User $fromUser User who did gib the potato.
     * @param array<\App\Model\Entity\User> $toUsers Users who will receive the potato.
     * @param \App\Event\MessageEvent $event The event.
     * @return string|bool false if not tagged, tagged_message_id if tagged
     */
    public function storeMessageIfTagged(
        User $fromUser,
        MessageEvent $event,
    ): bool|string {
        if (!str_contains($event->text, TaggedMessage::TAG)) {
            return false;
        }

        $taggedMessagesTable = $this->fetchTable('TaggedMessages');

        $taggedMessage = $taggedMessagesTable->newEntity([
            'sender_user_id' => $fromUser->id,
            'message' => $event->text,
            'permalink' => $event->permalink,
        ], [
            'accessibleFields' => [
                'sender_user_id' => true,
                'message' => true,
                'permalink' => true,
            ],
        ]);
        $taggedMessagesTable->saveOrFail($taggedMessage);

        metrics()->increment(
            key: 'gibpotato.message.tagged',
            value: 1,
            unit: MetricsUnit::custom('tags'),
        );

        return $taggedMessage->id;
    }
}
