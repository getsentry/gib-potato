<?php
declare(strict_types=1);

namespace App\Service;

class MessageUtility
{

    protected const VALID_REACTIONS = [
        'potato',
        'fries',
        'hotdog',
    ];

    protected const VALID_MESSAGE_EMOJIS = [
        ':potato:',
        ':fries:',
        ':hotdog:',
    ];

    public static function validateReaction(string $reaction): bool
    {
        return in_array($reaction, self::VALID_REACTIONS);
    }

    public static function validateMessage(string $message): bool
    {
        foreach (self::VALID_MESSAGE_EMOJIS as $validEmoji) {
            $containsValidEmoji = str_contains($message, $validEmoji);
            if ($containsValidEmoji === true) {
                return true;
            }
        }

        return false;
    }
}