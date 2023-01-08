<?php
declare(strict_types=1);

namespace App\Service\Event;

use App\Service\AwardService;
use App\Service\UserService;
use App\Service\Validation\Exception\PotatoException;
use App\Service\Validation\Validation;

class ReactionAddedEvent extends AbstractEvent
{
    public int $amount;
    public string $sender;
    public array $receivers;
    public string $channel;
    public string $text;
    public string $permalink;
    public string $reaction;
    public string $timestamp;
    public ?string $threadTimestamp;

    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_REACTION_ADDED;
        $this->amount = $event['amount'];
        $this->sender = $event['sender'];
        $this->receivers = $event['receivers'];
        $this->channel = $event['channel'];
        $this->text = $event['text'];
        $this->permalink = $event['permalink'];
        $this->reaction = $event['reaction'];
        $this->timestamp = $event['timestamp'];
        $this->eventTimestamp = $event['event_timestamp'];
        $this->threadTimestamp = $event['thread_timestamp'] ?? null;
    }

    public function process()
    {
        $userService = new UserService();
        $awardService = new AwardService();

        $fromUser = $userService->createOrUpdateUser($this->sender);
        $validator = new Validation(
            event: $this,
            sender: $fromUser,
        );

        try {
            $validator
                ->amount()
                ->receivers()
                ->sender();
        } catch (PotatoException $e) {
            $this->slackClient->postEphemeral(
                channel: $this->channel,
                user: $this->sender,
                text: $e->getMessage(),
                threadTimestamp: $this->threadTimestamp,
            );

            return;
        }

        foreach ($this->receivers as $receiver) {
            $toUser = $userService->createOrUpdateUser($receiver);
            $awardService->gib(
                fromUser: $fromUser,
                toUser: $toUser, 
                amount: $this->amount,
                type: 'potato',
            );
        }
    }
}
