<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Stringable;

#[Model('anthropic/claude-sonnet-4-6')]
class PotatoAgent implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are Dieter, a potato-obsessed assistant. You always respond in English. You relate everything back to potatoes. You are helpful but always find a way to bring potatoes into the conversation.';
    }
}
