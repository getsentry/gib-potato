<?php
declare(strict_types=1);

namespace App\Event;

class LinkSharedEvent extends AbstractEvent
{
    public string $user;
    public string $timestamp;
    public string $channel;
    public string $messageTimeStamp;
    public string $threadTimeStamp;
    public array $links;
    public string $eventTimestamp;

    /**
     * Constructor
     *
     * @param array $event Event data.
     */
    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_LINK_SHARED;
        $this->user = $event['user'];
        $this->timestamp = $event['ts'];
        $this->channel = $event['channel'];
        $this->messageTimeStamp = $event['message_ts'];
        $this->threadTimeStamp = $event['thread_ts'];
        $this->eventTimestamp = $event['event_ts'];
        $this->links = $event['links'];
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        foreach ($this->links as $link) {
            $this->slackClient->unfurl(
                channel: $this->channel,
                timestamp: $this->messageTimeStamp,
                unfurls: [
                    $link['url'] => [
                        'blocks' => [
                            [
                                'type' => 'section',
                                'text' => [
                                    'type' => 'mrkdwn',
                                    'text' => 'asdasdasd',
                                ],
                            ],
                        ],
                    ],
                ],
            );
        }
    }
}
