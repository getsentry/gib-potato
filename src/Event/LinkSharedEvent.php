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
            $message = $this->fetchDiscordMessage($link['url']);
            if ($message !== null) {
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
                                        'text' => $message,
                                    ],
                                ],
                            ],
                        ],
                    ],
                );
            }
        }
    }

    /**
     * @param string $url The Discord message URL
     * @return string|null The message content
     */
    private function fetchDiscordMessage(string $url): ?string
    {
        if (!preg_match('#^https://discord\.com/channels/\d+/\d+/\d+$#', $url)) {
            return null;
        }

        $parts = explode('/', $url);
        $channelId = $parts[5];
        $messageId = $parts[6];

        $message = $this->discordClient->getMessage($channelId, $messageId);

        return $message;
    }
}
