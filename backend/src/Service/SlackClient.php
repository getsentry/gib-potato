<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Http\Client;

class SlackClient
{
    protected const SLACK_API_URL = 'slack.com/api/';

    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'host' => self::SLACK_API_URL,
            'scheme' => 'https',
            'headers' => [
                'Authorization' => 'Bearer ' . env('SLACK_BOT_USER_OAUTH_TOKEN'),
            ],
        ]);
    }

    /**
     * @see https://api.slack.com/methods/chat.postMessage
     */
    public function postMessage(string $channel, string $text): void
    {
        $this->client->post('chat.postMessage', [
            'channel' => $channel,
            'text' => $text,
        ]);
    }

    /**
     * @see https://api.slack.com/methods/chat.postEphemeral
     */
    public function postEphemeral(string $channel, string $userId, string $text): void
    {
        $this->client->post('chat.postEphemeral', [
            'channel' => $channel,
            'user' => $userId,
            'text' => $text,
        ]);
    }

    /**
     * @see https://api.slack.com/methods/conversations.history
     */
    public function getSlackMessage(string $channel, string $timestamp): ?array
    {
        $response = $this->client->get('conversations.history', [
            'channel' => $channel,
            'latest' => $timestamp,
            'inclusive' => true,
            'limit' => 1
        ]);

        if ($response->isSuccess()) {
            $json = $response->getJson();
            // We expect one single message in the response
            if (!empty($json) && count($json['messages']) === 1) {
                return $json['messages'][0];
            }
        }

        return [];
    }
}