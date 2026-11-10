<?php

namespace App\Http\Controllers;

use App\Ai\Agents\PotatoAgent;
use App\Ai\PromptBuilder;
use App\Http\Requests\SlackEventRequest;
use App\Models\SlackThread;
use App\Models\User;
use App\Services\SlackClient;
use Illuminate\Http\JsonResponse;

class SlackController extends Controller
{
    public function event(SlackEventRequest $request, SlackClient $slack): JsonResponse
    {
        $user = User::firstOrCreate(
            ['slack_user_id' => $request->validated('sender')],
        );

        $messageTs = $request->messageTs();
        $threadTs = $request->threadTs();
        $threadKey = $request->threadKey();
        $channel = $request->validated('channel');

        if ($threadKey) {
            $slack->setAgentSessionStatus($channel, $threadKey, 'processing');
        }

        try {
            $prompt = $this->buildPrompt($request, $slack);
            $response = $this->promptAgent($user, $channel, $threadKey, $prompt);

            $text = trim((string) $response);

            if ($text !== '') {
                $slack->postMessage($channel, $text, $threadTs ?? $messageTs);
            }
        } finally {
            if ($threadKey) {
                $slack->setAgentSessionStatus($channel, $threadKey, 'active');
            }
        }

        return response()->json(['ok' => true]);
    }

    private function buildPrompt(SlackEventRequest $request, SlackClient $slack): string
    {
        $builder = PromptBuilder::for($request->validated('text'));

        if ($request->threadTs()) {
            $parentText = $slack->fetchMessage($request->validated('channel'), $request->threadTs());

            if ($parentText) {
                $builder->withThreadMessage('user', $parentText);
            }
        }

        return $builder->build();
    }

    private function promptAgent(User $user, string $channel, ?string $threadKey, string $prompt): mixed
    {
        $slackThread = $threadKey
            ? SlackThread::where('channel', $channel)->where('thread_ts', $threadKey)->first()
            : null;

        $agent = new PotatoAgent($channel, $user->slack_user_id);

        if ($slackThread) {
            return $agent->continue($slackThread->conversation_id, as: $user)->prompt($prompt);
        }

        $response = $agent->forUser($user)->prompt($prompt);

        if ($threadKey && $response->conversationId) {
            SlackThread::create([
                'channel' => $channel,
                'thread_ts' => $threadKey,
                'conversation_id' => $response->conversationId,
            ]);
        }

        return $response;
    }
}
