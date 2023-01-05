<?php
declare(strict_types=1);

namespace App\Service\Event;

use App\Service\Validation\Exception\PotatoException;
use App\Service\Validation\Validation;

class MessageEvent extends AbstractEvent
{
    protected int $amount;
    protected string $sender;
    protected array $receivers;
    protected string $channel;
    protected string $text;
    protected string $permalink;
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
        $this->timestamp = $event['timestamp'];
        $this->eventTimestamp = $event['event_timestamp'];
    }

    public function process()
    {
        try {
            Validation::amount($this->amount, count($this->receivers));
        } catch (PotatoException $e) {
            $this->slackClient->postEphemeral(
                channel: $this->channel,
                user: $this->sender,
                text: $e->getMessage(),
            );

            return;
        }

        
    }
}
