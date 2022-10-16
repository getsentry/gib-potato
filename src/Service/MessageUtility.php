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

    public static function parseType(string $message): string|false
    {
        foreach (self::VALID_MESSAGE_EMOJIS as $validEmoji) {
            $validEmojiCount = substr_count($message, $validEmoji);
            if ($validEmojiCount > 0) {
                // Strip : : from $validEmoji
                $result[] = str_replace(':', '', $validEmoji);
            }
        }

        // The only valid result is one type
        if (count($result) === 1) {
            return $result[0];
        }

        return false;
    }

    public static function parseAmount(string $message): int|false
    {
        // As we already checked for only one type of reaction
        // in self::parseReaction, we can return early here
        foreach (self::VALID_MESSAGE_EMOJIS as $validEmoji) {
            $validEmojiCount = substr_count($message, $validEmoji);
            if ($validEmojiCount > 0 && $validEmojiCount <= 5) {
                return $validEmojiCount;
            }
        }

        return false;
    }
}
