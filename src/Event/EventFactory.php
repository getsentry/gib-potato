<?php
declare(strict_types=1);

namespace App\Event;

use Exception;
use Sentry\SentrySdk;

class EventFactory
{
    /**
     * @param array $data Event data.
     * @return \App\Event\AbstractEvent
     * @throws \Exception
     */
    public static function createEvent(array $data): AbstractEvent
    {
        $eventType = $data['type'] ?? null;

        if ($eventType === null) {
            throw new Exception('Empty event type');
        }

        SentrySdk::getCurrentHub()->configureScope(function ($scope) use ($eventType): void {
            $scope->setTag('event_type', $eventType);
        });
        SentrySdk::getCurrentHub()->getTransaction()->setName(
            SentrySdk::getCurrentHub()->getTransaction()->getName() . ' - ' . $eventType,
        );

        return match ($eventType) {
            AbstractEvent::TYPE_MESSAGE => new MessageEvent($data),
            AbstractEvent::TYPE_DIRECT_MESSAGE => new DirectMessageEvent($data),
            AbstractEvent::TYPE_REACTION_ADDED => new ReactionAddedEvent($data),
            AbstractEvent::TYPE_APP_MENTION => new AppMentionEvent($data),
            AbstractEvent::TYPE_APP_HOME_OPENED => new AppHomeOpenedEvent($data),
            AbstractEvent::TYPE_SLASH_COMMAND => new SlashCommandEvent($data),
            AbstractEvent::TYPE_INTERACTIONS_CALLBACK => new InteractionsCallbackEvent($data),
            AbstractEvent::TYPE_LINK_SHARED => new LinkSharedEvent($data),
            default => throw new Exception('Unknown event type'),
        };
    }
}
