<?php
declare(strict_types=1);

namespace App\Service\Event;

class ReactionAddedEvent extends AbstractEvent
{
    protected int $amount;
    protected string $sender;
    protected array $receivers;
    protected string $channel;
    protected string $text;
    protected string $permalink;
    protected string $reaction;
    protected string $timestamp;

    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_MESSAGE;
        $this->amount = $event['amount'];
        $this->sender = $event['sender'];
        $this->receivers = $event['receivers'];
        $this->channel = $event['channel'];
        $this->text = $event['text'];
        $this->permalink = $event['permalink'];
        $this->reaction = $event['reaction'];
        $this->timestamp = $event['timestamp'];
        $this->eventTimestamp = $event['event_timestamp'];
    }

    public function process()
    {
        $this->slackClient->postMessage(
            $this->receivers[0],
            sprintf('<@%s> did gib you *%s* %s ', $this->sender, $this->amount, '🥔'),
        );
    }
}
