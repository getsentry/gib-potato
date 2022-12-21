<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Event\Event;

class MessageEvent extends AbstractEvent
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
        // Check potato amount
        if (
            $this->amount < 1 || $this->amount > 5) {
            $this->slackClient->postEphemeral(
                channel: $this->channel,
                user: $this->sender,
                text: 'Not enough :potato: to gib... 😥',
            );

            return;
        }
    }
}
