<?php
declare(strict_types=1);

namespace App\Service;

use App\Event\MessageEvent;
use App\Event\ReactionAddedEvent;
use App\Http\SlackClient;
use App\Model\Entity\User;
use Cake\ORM\Locator\LocatorAwareTrait;

class NotificationService
{
    use LocatorAwareTrait;

    protected SlackClient $slackClient;

    public function __construct()
    {
        $this->slackClient = new SlackClient();
    }

    public function notifyUsers(
        User $fromUser,
        array $toUsers,
        MessageEvent|ReactionAddedEvent $event,
    ) {
        $toUserNames = [];
        foreach ($toUsers as $toUser) {
            $toUserNames[] =  sprintf('<@%s>', $toUser->slack_user_id);

            if ($toUser->notifications['received'] === true) {
                $receivedMessage = sprintf('<@%s> did gib you *%s* %s.', $fromUser->slack_user_id, $event->amount, $event->reaction);
                $receivedMessage .= PHP_EOL;
                $receivedMessage .= sprintf('> %s', $event->permalink);
    
                $this->slackClient->postMessage(
                    channel: $toUser->slack_user_id,
                    text: $receivedMessage,
                );
            }
        }

        if ($fromUser->notifications['sent'] === true) {
            $potatoLeftToday = $fromUser->potatoLeftToday();

            $gibMessage = sprintf('You did gib *%s* %s to %s.', $event->amount * count($toUserNames), $event->reaction, implode(', ', $toUserNames));
            $gibMessage .= PHP_EOL;
            $gibMessage .= sprintf('You have *%s* :potato: left. Your potato reset in *%s hours* and *%s minutes*.', $potatoLeftToday, $fromUser->potatoResetInHours(), $fromUser->potatoResetInMinutes());
            $gibMessage .= PHP_EOL;
            $gibMessage .= sprintf('> %s', $event->permalink);

            $this->slackClient->postMessage(
                channel: $fromUser->slack_user_id,
                text: $gibMessage,
            );
        }
    }
}