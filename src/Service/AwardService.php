<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
use App\Model\Table\MessagesTable;
use App\Service\Event\MessageEvent;
use App\Service\Event\ReactionAddedEvent;
use Cake\ORM\Locator\LocatorAwareTrait;

class AwardService
{
    use LocatorAwareTrait;

    protected SlackClient $slackClient;

    public function __construct()
    {
        $this->slackClient = new SlackClient();
    }

    public function gib(
        User $fromUser,
        User $toUser,
        MessageEvent|ReactionAddedEvent $event,
    ) {
        $messagesTable = $this->fetchTable('Messages');

        $message = $messagesTable->newEntity([
            'sender_user_id' => $fromUser->id,
            'receiver_user_id' => $toUser->id,
            'amount' => $event->amount,
            'type' => str_replace(':', '', $event->reaction),
        ], [
            'accessibleFields' => [
                'sender_user_id' => true,
                'receiver_user_id' => true,
                'amount' => true,
                'type' => true,
            ],
        ]);
        $messagesTable->saveOrFail($message);

        if ($fromUser->notifications['sent'] === true) {
            $potatoLeftToday = $fromUser->potatoLeftToday();

            $gibMessage = sprintf('You did gib *%s* %s to <@%s>.', $event->amount, $event->reaction, $toUser->slack_user_id);
            $gibMessage .= PHP_EOL;
            $gibMessage .= sprintf('You have *%s* :potato: left.', $potatoLeftToday);
            $gibMessage .= PHP_EOL;
            $gibMessage .= sprintf('> %s', $event->permalink);

            $this->slackClient->postMessage(
                channel: $fromUser->slack_user_id,
                text: $gibMessage,
            );
        }

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
}
