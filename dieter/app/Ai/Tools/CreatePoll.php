<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreatePoll implements Tool
{
    public function __construct(
        public string $channel,
        public string $slackUserId,
    ) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Create a poll in a Slack channel. Only use this when the user explicitly asks to create a poll. You MUST have all of the following from the user before calling this tool: a title (the poll question), between 2 and 9 options, and whether the poll should be anonymous. Never invent or guess any of these — if the user has not provided them, ask for the missing details. When the user is in the middle of creating a poll and sends a follow-up message, treat it as providing the missing poll details rather than a new request. Do not call this tool until you have every detail confirmed.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $backendUrl = config('services.backend.url');
        $backendToken = config('services.backend.token');

        if (! $backendUrl || ! $backendToken) {
            return 'Poll creation is not configured. The backend URL and token must be set.';
        }

        $response = Http::timeout(10)
            ->connectTimeout(5)
            ->withHeader('Authorization', $backendToken)
            ->post("{$backendUrl}/polls", [
                'title' => $request['title'],
                'options' => $request['options'],
                'channel' => $this->channel,
                'user_slack_id' => $this->slackUserId,
                'anonymous' => $request['anonymous'] ?? false,
            ]);

        if ($response->status() === 422) {
            $error = $response->json('error', 'Invalid poll data.');

            return "Could not create the poll: {$error}";
        }

        if ($response->failed()) {
            return 'Failed to create the poll. The backend service might be unavailable.';
        }

        $data = $response->json();

        return sprintf(
            'Poll "%s" has been created and posted to the Slack channel. Do not reply to the user — the poll is the response.',
            $data['title'] ?? $request['title'],
        );
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('The poll question or title')->required(),
            'options' => $schema->array()->items($schema->string())->min(2)->max(9)->description('The poll answer options (between 2 and 9)')->required(),
            'anonymous' => $schema->boolean()->description('Whether the poll should be anonymous'),
        ];
    }
}
