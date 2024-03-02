<?php
declare(strict_types=1);

namespace App\Service;

use App\Event\MessageEvent;
use App\Event\ReactionAddedEvent;
use App\Http\SlackClient;
use App\Model\Entity\User;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;

class NotificationService
{
    use LocatorAwareTrait;

    protected SlackClient $slackClient;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->slackClient = new SlackClient();
    }

    /**
     * @param \App\Model\Entity\User $fromUser User who did gib the potato
     * @param array<\App\Model\Entity\User> $toUsers Users who will receive the potato
     * @param \App\Event\MessageEvent|\App\Event\ReactionAddedEvent $event The event.
     * @return void
     */
    public function notifyUsers(
        User $fromUser,
        array $toUsers,
        MessageEvent|ReactionAddedEvent $event,
    ): void {
        $toUserNames = [];
        foreach ($toUsers as $toUser) {
            $toUserNames[] = sprintf('<@%s>', $toUser->slack_user_id);

            if ($toUser->notifications['received'] === true) {
                $receivedMessage = sprintf(
                    '<@%s> did gib you *%s* %s.',
                    $fromUser->slack_user_id,
                    $event->amount,
                    $event->reaction,
                );
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

            $gibMessage = sprintf(
                'You did gib *%s* %s to %s.',
                $event->amount * count($toUserNames),
                $event->reaction,
                implode(', ', $toUserNames),
            );
            $gibMessage .= PHP_EOL;
            $gibMessage .= sprintf(
                'You have *%s* :potato: left. Your potato do reset in *%s hours* and *%s minutes*.',
                $potatoLeftToday,
                $fromUser->potatoResetInHours(),
                $fromUser->potatoResetInMinutes(),
            );
            $gibMessage .= PHP_EOL;
            $gibMessage .= sprintf('> %s', $event->permalink);

            $this->slackClient->postMessage(
                channel: $fromUser->slack_user_id,
                text: $gibMessage,
            );
        }
    }

    /**
     * @param \App\Model\Entity\User $fromUser User who did gib the potato
     * @param string $taggedMessageId id of the tagged message
     * @param \App\Event\MessageEvent $event The event.
     * @return void
     */
    public function notifyChannelNewQuickwin(
        User $fromUser,
        string $taggedMessageId,
        MessageEvent $event,
    ): void {
        $blocks = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "<@{$fromUser->slack_user_id}> posted a new <" . $event->permalink . '|#quickwin>!',
                ],
            ],
            [
                'type' => 'divider',
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '<' . Router::url('/quickwins', true) . '?id=' . $taggedMessageId . '|Visit Hall of Fame>',
                ],
            ],

        ];

        $this->slackClient->postBlocks(
            channel: env('POTATO_CHANNEL'),
            blocks: json_encode($blocks),
        );
    }
}
