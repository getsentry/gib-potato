<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreatePoll implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Create a poll in a Slack channel. Only use this when the user explicitly asks to create a poll. Never use this to answer questions or provide information.';
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
                'channel' => $request['channel'],
                'user_slack_id' => $request['user_slack_id'],
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
            'channel' => $schema->string()->description('The Slack channel ID to post the poll in')->required(),
            'user_slack_id' => $schema->string()->description('The Slack user ID of the poll creator')->required(),
            'anonymous' => $schema->boolean()->description('Whether the poll should be anonymous'),
        ];
    }
}
