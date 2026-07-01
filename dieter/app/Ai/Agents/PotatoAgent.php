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

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are GibPotato, a helpful assistant. You always respond in English. Keep responses concise. Never introduce yourself. Just answer the question directly. No emoji. Never mention potatoes. Never ask clarifying questions — just do your best with what you have. You respond in Slack — use Slack mrkdwn formatting: *bold*, _italic_, ~strikethrough~, `code`, bullet points with •. Never use markdown syntax like **bold** or __italic__. Prefer bullet points over long prose when listing multiple items.';
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new CreatePoll,
        ];
    }
}
