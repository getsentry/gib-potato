<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SlackClient
{
    public function addReaction(string $channel, string $timestamp, string $emoji): void
    {
        $this->post('reactions.add', [
            'channel' => $channel,
            'timestamp' => $timestamp,
            'name' => $emoji,
        ]);
    }

    public function removeReaction(string $channel, string $timestamp, string $emoji): void
    {
        $this->post('reactions.remove', [
            'channel' => $channel,
            'timestamp' => $timestamp,
            'name' => $emoji,
        ]);
    }

    public function setAssistantStatus(string $channel, string $threadTs, string $status = 'is thinking...'): void
    {
        $this->post('assistant.threads.setStatus', [
            'channel_id' => $channel,
            'thread_ts' => $threadTs,
            'status' => $status,
        ]);
    }

    public function clearAssistantStatus(string $channel, string $threadTs): void
    {
        $this->setAssistantStatus($channel, $threadTs, '');
    }

    public function fetchMessage(string $channel, string $timestamp): ?string
    {
        $token = $this->token();

        if (! $token) {
            return null;
        }

        $response = Http::withToken($token)
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

    private function post(string $endpoint, array $payload): void
    {
        $token = $this->token();

        if (! $token) {
            return;
        }

        Http::withToken($token)
            ->post("https://slack.com/api/{$endpoint}", $payload);
    }

    private function token(): ?string
    {
        return config('services.slack.notifications.bot_user_oauth_token');
    }
}
