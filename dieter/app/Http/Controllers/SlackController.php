<?php

namespace App\Http\Controllers;

use App\Ai\Agents\PotatoAgent;
use App\Models\SlackThread;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SlackController extends Controller
{
    public function event(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string'],
            'sender' => ['required', 'string'],
            'channel' => ['required', 'string'],
            'text' => ['required', 'string'],
            'timestamp' => ['nullable', 'string'],
            'thread_timestamp' => ['nullable', 'string'],
        ]);

        $messageTs = $validated['timestamp'] ?? null;
        $threadTs = $validated['thread_timestamp'] ?? null;

        $user = User::firstOrCreate(
            ['slack_user_id' => $validated['sender']],
        );

        $assistantThreadTs = $threadTs ?? $messageTs;

        if ($messageTs) {
            $this->addReaction($validated['channel'], $messageTs, 'potato');
        }

        if ($assistantThreadTs) {
            $this->setAssistantStatus($validated['channel'], $assistantThreadTs);
        }

        $threadContext = '';
        if ($threadTs) {
            $parentText = $this->fetchMessage($validated['channel'], $threadTs);
            if ($parentText) {
                $threadContext = sprintf(
                    "\n\nThis message was posted in a thread. The parent message is: \"%s\"",
                    $parentText,
                );
            }
        }

        $prompt = sprintf(
            "The user's Slack ID is %s and the current channel ID is %s.%s\n\n%s",
            $validated['sender'],
            $validated['channel'],
            $threadContext,
            $validated['text'],
        );

        $threadKey = $assistantThreadTs;
        $slackThread = $threadKey
            ? SlackThread::where('channel', $validated['channel'])->where('thread_ts', $threadKey)->first()
            : null;

        $agent = new PotatoAgent;

        if ($slackThread) {
            $response = $agent->continue($slackThread->conversation_id, $user)->prompt($prompt);
        } else {
            $response = $agent->forUser($user)->prompt($prompt);

            if ($threadKey && $agent->currentConversation()) {
                SlackThread::create([
                    'channel' => $validated['channel'],
                    'thread_ts' => $threadKey,
                    'conversation_id' => $agent->currentConversation(),
                ]);
            }
        }

        if ($assistantThreadTs) {
            $this->clearAssistantStatus($validated['channel'], $assistantThreadTs);
        }

        if ($messageTs) {
            $this->removeReaction($validated['channel'], $messageTs, 'potato');
            $this->addReaction($validated['channel'], $messageTs, 'white_check_mark');
        }

        $text = trim((string) $response);

        if ($text !== '') {
            $this->postToSlack($validated['channel'], $text, $threadTs ?? $messageTs);
        }

        return response()->json(['ok' => true]);
    }

    private function slackToken(): ?string
    {
        return config('services.slack.notifications.bot_user_oauth_token');
    }

    private function addReaction(string $channel, string $timestamp, string $emoji): void
    {
        $token = $this->slackToken();

        if (! $token) {
            return;
        }

        Http::withToken($token)
            ->post('https://slack.com/api/reactions.add', [
                'channel' => $channel,
                'timestamp' => $timestamp,
                'name' => $emoji,
            ]);
    }

    private function removeReaction(string $channel, string $timestamp, string $emoji): void
    {
        $token = $this->slackToken();

        if (! $token) {
            return;
        }

        Http::withToken($token)
            ->post('https://slack.com/api/reactions.remove', [
                'channel' => $channel,
                'timestamp' => $timestamp,
                'name' => $emoji,
            ]);
    }

    private function setAssistantStatus(string $channel, string $threadTs): void
    {
        $token = $this->slackToken();

        if (! $token) {
            return;
        }

        Http::withToken($token)
            ->post('https://slack.com/api/assistant.threads.setStatus', [
                'channel_id' => $channel,
                'thread_ts' => $threadTs,
                'status' => 'is thinking...',
            ]);
    }

    private function clearAssistantStatus(string $channel, string $threadTs): void
    {
        $token = $this->slackToken();

        if (! $token) {
            return;
        }

        Http::withToken($token)
            ->post('https://slack.com/api/assistant.threads.setStatus', [
                'channel_id' => $channel,
                'thread_ts' => $threadTs,
                'status' => '',
            ]);
    }

    private function fetchMessage(string $channel, string $timestamp): ?string
    {
        $token = $this->slackToken();

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

    private function postToSlack(string $channel, string $text, ?string $threadTs = null): void
    {
        $token = $this->slackToken();

        if (! $token) {
            return;
        }

        $payload = [
            'channel' => $channel,
            'text' => $text,
        ];

        if ($threadTs) {
            $payload['thread_ts'] = $threadTs;
        }

        Http::withToken($token)
            ->post('https://slack.com/api/chat.postMessage', $payload);
    }
}
