<?php
declare(strict_types=1);

namespace App\Service;

class AppMentionEvent extends AbstractEvent
{
    protected string $sender;
    protected string $channel;
    protected string $text;
    protected string $timestamp;

    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_APP_MENTION;
        $this->sender = $event['sender'];
        $this->channel = $event['channel'];
        $this->text = $event['text'];
        $this->timestamp = $event['timestamp'];
    }

    public function process()
    {
        $this->slackClient->postMessage(
            channel: $this->channel,
            text: 'Potato, potato :potato:',
        );
    }
}
