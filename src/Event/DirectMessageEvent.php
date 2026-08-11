<?php
declare(strict_types=1);

namespace App\Event;

use App\Service\UserService;

class DirectMessageEvent extends AbstractEvent
{
    public string $sender;
    public string $channel;
    public string $text;
    public string $timestamp;

    /**
     * Constructor
     *
     * @param array $event Event data.
     */
    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_DIRECT_MESSAGE;
        $this->sender = $event['sender'];
        $this->channel = $event['channel'];
        $this->text = $event['text'];
        $this->timestamp = $event['timestamp'];
        $this->eventTimestamp = $event['event_timestamp'];
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        if ($this->text === 'potato') {
            $userService = new UserService();

            $user = $userService->getOrCreateUser($this->sender);

            $message = sprintf(
                'You have *%s* :potato: and *%s* :admission_tickets: left to gib today.',
                $user->potatoLeftToday(),
                $user->vouchersLeftToday(),
            );
            $message .= PHP_EOL;
            $message .= sprintf(
                'They will reset in *%s hours* and *%s minutes*.',
                $user->potatoResetInHours(),
                $user->potatoResetInMinutes(),
            );

            $this->slackClient->postMessage(
                channel: $this->channel,
                text: $message,
            );

            return;
        }
    }
}
