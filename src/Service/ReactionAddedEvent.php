<?php
declare(strict_types=1);

namespace App\Service;

class ReactionAddedEvent extends AbstractEvent
{
    protected int $amount;
    protected string $sender;
    protected array $receiver;
    protected string $channel;
    protected string $text;
    protected string $timestamp;

    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_MESSAGE;
        $this->amount = $event['amount'];
        $this->sender = $event['sender'];
        $this->receiver = $event['receiver'];
        $this->channel = $event['channel'];
        $this->text = $event['text'];
        $this->timestamp = $event['timestamp'];
    }

    public function process()
    {
    }
}
