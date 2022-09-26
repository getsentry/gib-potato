<?php
declare(strict_types=1);

namespace App\Service;

class EventFactory
{
    public const TYPE_MESSAGE = 'message';

    public const TYPE_REACTION_ADDED = 'reaction_added';

    public static function createEvent(array $requestData)
    {
        if (empty($requestData)) {
            return new NoOpEvent();
        }

        $eventType = $requestData['type'] ?? null;

        switch ($eventType) {
            case self::TYPE_MESSAGE:
                return new MessageEvent($requestData);
            case self::TYPE_REACTION_ADDED:
                return new ReactionAddedEvent($requestData);
            default:
                return new NoOpEvent();
        }
    }
}