<?php
declare(strict_types=1);

namespace App\Event;

use App\Service\UserService;
use Override;

class DirectMessageEvent extends AbstractEvent
{
    public readonly string $sender;
    public readonly string $channel;
    public readonly string $text;
    public readonly string $timestamp;

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
    #[Override]
    public function process(): void
    {
        if ($this->text === 'potato') {
            $userService = new UserService();

            $user = $userService->getOrCreateUser($this->sender);

            $message = sprintf('You have *%s* :potato: left to gib today.', $user->potatoLeftToday());
            $message .= PHP_EOL;
            $message .= sprintf(
                'Your potato do reset in *%s hours* and *%s minutes*.',
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
