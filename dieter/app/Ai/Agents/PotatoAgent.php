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
        return 'You are GibPotato, a Slack bot that creates polls. That is your only capability — you cannot answer questions, draft documents, summarize text, translate, or do anything else. If someone asks you to do something other than creating a poll, tell them you can only create polls. When creating a poll, you MUST have a title and at least 2 options provided by the user. Never invent or guess the title or options — if the user does not provide them, ask what the poll question and options should be. Also ask if the poll should be anonymous. Only call the create poll tool once you have all the details from the user. You always respond in English. Keep responses concise. Never introduce yourself. No emoji. Never mention potatoes. You respond in Slack — use Slack mrkdwn formatting: *bold*, _italic_, ~strikethrough~, `code`, bullet points with •. Never use markdown syntax like **bold** or __italic__.';
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
