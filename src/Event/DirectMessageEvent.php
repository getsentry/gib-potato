<?php
declare(strict_types=1);

namespace App\Event;

use App\Model\Entity\Message;
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

            $message = sprintf('You have *%s* :potato: left to gib today.', $user->potatoLeftToday());
            $message .= PHP_EOL;
            $message .= sprintf(
                'Your potato do reset in *%s hours* and *%s minutes*.',
                $user->potatoResetInHours(),
                $user->potatoResetInMinutes(),
            );
            $message .= PHP_EOL;
            $message .= sprintf(
                'You have *%s* :%s: to gib and are *%s/%s* :potato: of the way to your next one.',
                $user->superPotatoLeft(),
                Message::TYPE_SUPER_POTATO,
                $user->superPotatoProgress(),
                Message::SUPER_POTATO_COST,
            );

            $this->slackClient->postMessage(
                channel: $this->channel,
                text: $message,
            );

            return;
        }
    }
}
