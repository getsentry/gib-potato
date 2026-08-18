<?php

namespace App\Http\Controllers;

use App\Ai\Agents\PotatoAgent;
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

        if ($messageTs) {
            $slack->addReaction($channel, $messageTs, 'potato');
        }

        if ($threadKey) {
            $slack->setAssistantStatus($channel, $threadKey);
        }

        $prompt = $this->buildPrompt($request, $slack);
        $response = $this->promptAgent($user, $channel, $threadKey, $prompt);

        if ($threadKey) {
            $slack->clearAssistantStatus($channel, $threadKey);
        }

        if ($messageTs) {
            $slack->removeReaction($channel, $messageTs, 'potato');
            $slack->addReaction($channel, $messageTs, 'white_check_mark');
        }

        $text = trim((string) $response);

        if ($text !== '') {
            $slack->postMessage($channel, $text, $threadTs ?? $messageTs);
        }

        return response()->json(['ok' => true]);
    }

    private function buildPrompt(SlackEventRequest $request, SlackClient $slack): string
    {
        $threadContext = '';

        if ($request->threadTs()) {
            $parentText = $slack->fetchMessage($request->validated('channel'), $request->threadTs());

            if ($parentText) {
                $threadContext = sprintf(
                    "\n\nThis message was posted in a thread. The parent message is: \"%s\"",
                    $parentText,
                );
            }
        }

        return sprintf(
            "The user's Slack ID is %s and the current channel ID is %s.%s\n\n%s",
            $request->validated('sender'),
            $request->validated('channel'),
            $threadContext,
            $request->validated('text'),
        );
    }

    private function promptAgent(User $user, string $channel, ?string $threadKey, string $prompt): mixed
    {
        $slackThread = $threadKey
            ? SlackThread::where('channel', $channel)->where('thread_ts', $threadKey)->first()
            : null;

        $agent = new PotatoAgent;

        if ($slackThread) {
            return $agent->continue($slackThread->conversation_id, $user)->prompt($prompt);
        }

        $response = $agent->forUser($user)->prompt($prompt);

        if ($threadKey && $agent->currentConversation()) {
            SlackThread::create([
                'channel' => $channel,
                'thread_ts' => $threadKey,
                'conversation_id' => $agent->currentConversation(),
            ]);
        }

        return $response;
    }
}
