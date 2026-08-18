<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CreatePoll;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

#[Model('gpt-5.6-luna')]
class PotatoAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        public string $channel,
        public string $slackUserId,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are GibPotato, a Slack bot that creates polls. That is all you do. If someone greets you or makes small talk, just say hi back — keep it short and casual. If someone asks you to do something other than creating a poll and you are NOT in the middle of collecting poll details, tell them you can only create polls. When you have already asked the user for poll details (title, options, anonymity), treat their next messages as providing those details — not as standalone requests. Never list instructions or usage guides unless the user explicitly asks how to create a poll. You always respond in English. Keep responses concise. Never introduce yourself. No emoji. Never mention potatoes. You respond in Slack — use Slack mrkdwn formatting: *bold*, _italic_, ~strikethrough~, `code`, bullet points with •. Never use markdown syntax like **bold** or __italic__.';
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new CreatePoll($this->channel, $this->slackUserId),
        ];
    }
}
