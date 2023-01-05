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
    public function postEphemeral(string $channel, string $user, string $text): void
    {
        $this->client->post('chat.postEphemeral', [
            'channel' => $channel,
            'user' => $user,
            'text' => $text,
        ]);
    }

    /**
     * @see https://api.slack.com/methods/views.publish
     */
    public function publishView(string $user, array $view): void
    {
        $this->client->post('views.publish', [
            'user_id' => $user,
            'view' => json_encode($view),
        ]);
    }

    /**
     * @see https://api.slack.com/methods/users.info
     */
    public function getUser(string $user): ?array
    {
        $response = $this->client->get('users.info', [
            'user' => $user,
        ]);

        if ($response->isSuccess()) {
            $json = $response->getJson();

            return $json['user'];
        }

        return [];
    }
}
