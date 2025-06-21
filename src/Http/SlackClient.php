<?php
declare(strict_types=1);

namespace App\Http;

use function Cake\Core\env;
use function Sentry\logger;

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
                logger()->warn('Slack API error: https://api.slack.com/methods/chat.postMessage', attributes: [
                    'channel' => $channel,
                    'text' => $text,
                    'slack_response' => $json,
                ]);
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
                logger()->warn('Slack API error: https://api.slack.com/methods/chat.postMessage', attributes: [
                    'channel' => $channel,
                    'blocks' => $blocks,
                    'slack_response' => $json,
                ]);
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
                logger()->warn('Slack API error: https://api.slack.com/methods/chat.postEphemeral', attributes: [
                    'channel' => $channel,
                    'user' => $user,
                    'text' => $text,
                    'thread_ts' => $threadTimestamp,
                    'slack_response' => $json,
                ]);
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
                logger()->warn('Slack API error: https://api.slack.com/methods/views.publish', attributes: [
                    'user_id' => $user,
                    'view' => $view,
                    'slack_response' => $json,
                ]);
            }
        }
    }

    /**
     * @param string $triggerId The trigger ID to open the view for.
     * @param array $view The view to open.
     * @return void
     * @see https://api.slack.com/methods/views.open
     */
    public function openView(string $triggerId, array $view): void
    {
        $response = $this->client->post('views.open', [
            'trigger_id' => $triggerId,
            'view' => json_encode($view),
        ]);

        if ($response->isSuccess()) {
            $json = $response->getJson();

            if ($json['ok'] === false) {
                logger()->warn('Slack API error: https://api.slack.com/methods/views.open', attributes: [
                    'trigger_id' => $triggerId,
                    'view' => $view,
                    'slack_response' => $json,
                ]);
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
                logger()->warn('Slack API error: https://api.slack.com/methods/users.info', attributes: [
                    'user' => $user,
                    'slack_response' => $json,
                ]);
            }
        }

        return [];
    }

    /**
     * @param string $channel The channel to unfurl the links in.
     * @param string $timestamp The timestamp of the message to unfurl the links in.
     * @param array $unfurls The unfurls to send.
     * @return void
     * @see https://api.slack.com/methods/chat.unfurl
     */
    public function unfurl(string $channel, string $timestamp, array $unfurls): void
    {
        $response = $this->client->post('chat.unfurl', [
            'channel' => $channel,
            'ts' => $timestamp,
            'unfurls' => json_encode($unfurls),
        ]);

        if ($response->isSuccess()) {
            $json = $response->getJson();
            if ($json['ok'] === false) {
                logger()->warn('Slack API error: https://api.slack.com/methods/chat.unfurl', attributes: [
                    'channel' => $channel,
                    'timestamp' => $timestamp,
                    'unfurls' => $unfurls,
                    'slack_response' => $json,
                ]);
            }
        }
    }
}
