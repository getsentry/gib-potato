<?php
declare(strict_types=1);

namespace App\Http;

use function Sentry\captureMessage;
use function Sentry\withScope;

class SlackClient
{
    protected const SLACK_API_URL = 'slack.com/api';

    protected Client $client;

    /**
     * Constructor.
     */
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
     * @param string $channel The channel to send the message to.
     * @param string $text The text of the message to send.
     * @return void
     * @see https://api.slack.com/methods/chat.postMessage
     */
    public function postMessage(string $channel, string $text): void
    {
        $response = $this->client->post('chat.postMessage', [
            'channel' => $channel,
            'text' => $text,
        ]);

        if ($response->isSuccess()) {
            $json = $response->getJson();

            if ($json['ok'] === false) {
                withScope(function ($scope) use ($json, $channel, $text) {
                    $scope->setExtras([
                        'channel' => $channel,
                        'text' => $text,
                        'slack_response' => $json,
                    ]);
                    captureMessage('Slack API error: https://api.slack.com/methods/chat.postMessage');
                });
            }
        }
    }

    /**
     * @param string $channel The channel to send the message to.
     * @param string $blocks The blocks of the message to send.
     * @return void
     * @see https://api.slack.com/methods/chat.postMessage
     */
    public function postBlocks(string $channel, string $blocks): void
    {
        $response = $this->client->post('chat.postMessage', [
            'channel' => $channel,
            'blocks' => $blocks,
        ]);

        if ($response->isSuccess()) {
            $json = $response->getJson();

            if ($json['ok'] === false) {
                withScope(function ($scope) use ($json, $channel, $blocks) {
                    $scope->setExtras([
                        'channel' => $channel,
                        'blocks' => $blocks,
                        'slack_response' => $json,
                    ]);
                    captureMessage('Slack API error: https://api.slack.com/methods/chat.postMessage');
                });
            }
        }
    }

    /**
     * @param string $channel The channel to send the message to.
     * @param string $user The user to send the message to.
     * @param string $text The text of the message to send.
     * @param string|null $threadTimestamp The thread timestamp of the message.
     * @return void
     * @see https://api.slack.com/methods/chat.postEphemeral
     */
    public function postEphemeral(string $channel, string $user, string $text, ?string $threadTimestamp = null): void
    {
        $response = $this->client->post('chat.postEphemeral', [
            'channel' => $channel,
            'user' => $user,
            'text' => $text,
            'thread_ts' => $threadTimestamp,
        ]);

        if ($response->isSuccess()) {
            $json = $response->getJson();

            if ($json['ok'] === false) {
                withScope(function ($scope) use ($json, $channel, $user, $text, $threadTimestamp) {
                    $scope->setExtras([
                        'channel' => $channel,
                        'user' => $user,
                        'text' => $text,
                        'thread_ts' => $threadTimestamp,
                        'slack_response' => $json,
                    ]);
                    captureMessage('Slack API error: https://api.slack.com/methods/chat.postEphemeral');
                });
            }
        }
    }

    /**
     * @param string $user The user to publish the view for.
     * @param array $view The view to publish.
     * @return void
     * @see https://api.slack.com/methods/views.publish
     */
    public function publishView(string $user, array $view): void
    {
        $response = $this->client->post('views.publish', [
            'user_id' => $user,
            'view' => json_encode($view),
        ]);

        if ($response->isSuccess()) {
            $json = $response->getJson();

            if ($json['ok'] === false) {
                withScope(function ($scope) use ($json, $user, $view) {
                    $scope->setExtras([
                        'user_id' => $user,
                        'view' => $view,
                        'slack_response' => $json,
                    ]);
                    captureMessage('Slack API error: https://api.slack.com/methods/views.publish');
                });
            }
        }
    }

    /**
     * @param string $user The Slack user ID.
     * @return array
     * @see https://api.slack.com/methods/users.info
     */
    public function getUser(string $user): array
    {
        $response = $this->client->get('users.info', [
            'user' => $user,
        ]);

        if ($response->isSuccess()) {
            $json = $response->getJson();

            if ($json['ok'] === true) {
                return $json['user'];
            } else {
                withScope(function ($scope) use ($json, $user) {
                    $scope->setExtras([
                        'user' => $user,
                        'slack_response' => $json,
                    ]);
                    captureMessage('Slack API error: https://api.slack.com/methods/users.info');
                });
            }
        }

        return [];
    }
}
