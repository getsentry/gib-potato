<?php
declare(strict_types=1);

namespace App\Event;

use App\Event\Validation\Validation;
use App\Event\Validation\Exception\PotatoException;
use App\Service\AwardService;
use App\Service\UserService;

class MessageEvent extends AbstractEvent
{
    public int $amount;
    public string $sender;
    public array $receivers;
    public string $channel;
    public string $text;
    public string $reaction;
    public string $timestamp;
    public string $permalink;

    public ?string $threadTimestamp;

    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_MESSAGE;
        $this->amount = $event['amount'];
        $this->sender = $event['sender'];
        $this->receivers = $event['receivers'];
        $this->channel = $event['channel'];
        $this->text = $event['text'];
        $this->reaction = $event['reaction'];
        $this->timestamp = $event['timestamp'];
        $this->eventTimestamp = $event['event_timestamp'];
        $this->permalink = $event['permalink'];

        $this->threadTimestamp = $event['thread_timestamp'] ?? null;
    }

    public function process()
    {
        $userService = new UserService();
        $awardService = new AwardService();

        $fromUser = $userService->getOrCreateUser($this->sender);
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
            $toUser = $userService->getOrCreateUser($receiver);
            $awardService->gib(
                fromUser: $fromUser,
                toUser: $toUser, 
                event: $this,
            );
        }
    }
}