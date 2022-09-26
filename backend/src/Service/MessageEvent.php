<?php
declare(strict_types=1);

namespace App\Service;

class MessageEvent extends AbstractEvent
{
    protected array $eventData;

    public function __construct(array $eventData)
    {
        $this->eventData = $eventData;
    }

    public function process()
    {
        $messageText = $this->eventData['text'];
        $messageFromUser = $this->eventData['user'];
        $messageTimeStamp = $this->eventData['ts'];

        // Check for supported emoji in the message
        if (MessageUtility::validateMessage($messageText) === false) {
            return;
        }

        dlog($messageText);
        dlog($messageFromUser);
        dlog($messageTimeStamp);
    }
}