<?php
declare(strict_types=1);

namespace App\Event;

use App\Model\Entity\Poll;
use App\Service\PollService;
use App\Service\UserService;

class SlashCommandEvent extends AbstractEvent
{
    protected string $user;
    protected string $command;
    protected string $channel;
    protected string $text;
    protected string $triggerId;

    protected PollService $pollService;

    /**
     * Constructor
     *
     * @param array $event Event data.
     */
    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_SLASH_COMMAND;
        $this->user = $event['user'];
        $this->command = $event['command'];
        $this->channel = $event['channel'];
        $this->text = $event['text'];
        $this->triggerId = $event['trigger_id'];

        $this->pollService = new PollService();
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $this->pollService->triggerPollView($this->triggerId);
    }
}
