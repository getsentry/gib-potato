<?php
declare(strict_types=1);

namespace App\Http;

use function Cake\Core\env;
use function Sentry\logger;

class DiscordClient
{
    protected const DISCORD_API_URL = 'discord.com/api/v10';

    protected Client $client;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->client = new Client([
            'host' => self::DISCORD_API_URL,
            'scheme' => 'https',
            'headers' => [
                'Authorization' => 'Bot ' . env('DISCORD_BOT_TOKEN'),
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * @param string $channelId The channel ID
     * @param string $messageId The message ID
     * @return string|null The message content
     * @see https://discord.com/developers/docs/resources/message#get-channel-message
     */
    public function getMessage(string $channelId, string $messageId): ?string
    {
        $response = $this->client->get("channels/{$channelId}/messages/{$messageId}");

        if ($response->isSuccess()) {
            $json = $response->getJson();

            return $json['content'] ?? null;
        }

        logger()->warn('Discord API error: Failed to fetch message', attributes: [
            'channel_id' => $channelId,
            'message_id' => $messageId,
            'discord_response' => $response->getJson(),
        ]);

        return null;
    }
}
