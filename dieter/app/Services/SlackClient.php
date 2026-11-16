<?php

namespace App\Services;

use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Http;

readonly class SlackClient
{
    public function __construct(
        #[Config('services.slack.notifications.bot_user_oauth_token')]
        private ?string $token,
    ) {}

    public function setAgentSessionStatus(string $channel, string $threadTs, string $status): void
    {
        $this->post('agents.sessions.setStatus', [
            'channel_id' => $channel,
            'thread_ts' => $threadTs,
            'status' => $status,
        ]);
    }

    public function fetchMessage(string $channel, string $timestamp): ?string
    {
        if (! $this->token) {
            return null;
        }

        $response = Http::withToken($this->token)
            ->get('https://slack.com/api/conversations.history', [
                'channel' => $channel,
                'latest' => $timestamp,
                'inclusive' => true,
                'limit' => 1,
            ]);

        return $response->json('messages.0.text');
    }

    public function postMessage(string $channel, string $text, ?string $threadTs = null): void
    {
        $payload = [
            'channel' => $channel,
            'text' => $text,
        ];

        if ($threadTs) {
            $payload['thread_ts'] = $threadTs;
        }

        $this->post('chat.postMessage', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(string $endpoint, array $payload): void
    {
        if (! $this->token) {
            return;
        }

        Http::withToken($this->token)
            ->post("https://slack.com/api/{$endpoint}", $payload);
    }
}
