<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CreatePoll;
use App\Ai\Tools\FetchSpoonFoodMenu;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

#[Model('gpt-5.6-luna')]
#[Timeout(30)]
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
        return file_get_contents(__DIR__.'/potato-agent.md');
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
            new FetchSpoonFoodMenu,
        ];
    }
}
