<?php
declare(strict_types=1);

namespace App\Event;

use App\Event\Validation\Validation;
use App\Event\Validation\Exception\PotatoException;
use App\Service\AwardService;
use App\Service\UserService;

class DirectMessageEvent extends AbstractEvent
{
    public string $sender;
    public string $channel;
    public string $text;
    public string $timestamp;

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

    public function process()
    {
        if ($this->text === 'potato') {
            $this->slackClient->postMessage(
                channel: $this->channel,
                text: 'Potato!',
            );

            return;
        }
    }
}
