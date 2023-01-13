<?php
declare(strict_types=1);

namespace App\Service\Event;

use Exception;

class EventFactory
{
    public static function createEvent(array $data): AbstractEvent
    {
        $eventType = $data['type'] ?? null;

        switch ($eventType) {
            case AbstractEvent::TYPE_MESSAGE:
                return new MessageEvent($data);
            case AbstractEvent::TYPE_REACTION_ADDED:
                return new ReactionAddedEvent($data);
            case AbstractEvent::TYPE_APP_MENTION:
                return new AppMentionEvent($data);
            case AbstractEvent::TYPE_APP_HOME_OPENED:
                return new AppHomeOpenedEvent($data);
            default:
                throw new Exception('Unknown event type');
        }
    }
}
