<?php
declare(strict_types=1);

namespace App\Event;

use Exception;
use Sentry\SentrySdk;

class EventFactory
{
    public static function createEvent(array $data): AbstractEvent
    {
        $eventType = $data['type'] ?? null;

        if (null === $eventType) {
            throw new Exception('Unknown event type');
        }

        SentrySdk::getCurrentHub()->configureScope(function ($scope) use ($eventType) {
            $scope->setTag('event_type', $eventType);
        });
        SentrySdk::getCurrentHub()->getTransaction()->setName(
            SentrySdk::getCurrentHub()->getTransaction()->getName() . ' - ' . $eventType
        );

        switch ($eventType) {
            case AbstractEvent::TYPE_MESSAGE:
                return new MessageEvent($data);
            case AbstractEvent::TYPE_DIRECT_MESSAGE:
                return new DirectMessageEvent($data);
            case AbstractEvent::TYPE_REACTION_ADDED:
                return new ReactionAddedEvent($data);
            case AbstractEvent::TYPE_APP_MENTION:
                return new AppMentionEvent($data);
            case AbstractEvent::TYPE_APP_HOME_OPENED:
                return new AppHomeOpenedEvent($data);
        }
    }
}
