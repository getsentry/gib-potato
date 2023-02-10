<?php
declare(strict_types=1);

namespace App\Event;

class AppMentionEvent extends AbstractEvent
{
    protected string $sender;
    protected string $channel;
    protected string $text;

    /**
     * Constructor
     *
     * @param array $event Event data.
     */
    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_APP_MENTION;
        $this->sender = $event['sender'];
        $this->channel = $event['channel'];
        $this->text = $event['text'];
        $this->eventTimestamp = $event['event_timestamp'];
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
    }
}
