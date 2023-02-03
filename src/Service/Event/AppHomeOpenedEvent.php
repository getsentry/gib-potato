<?php
declare(strict_types=1);

namespace App\Service\Event;

use App\Service\UserService;

class AppHomeOpenedEvent extends AbstractEvent
{
    protected string $user;
    protected string $tab;
    protected string $timestamp;

    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_APP_HOME_OPENED;
        $this->user = $event['user'];
        $this->tab = $event['tab'];
        $this->eventTimestamp = $event['event_timestamp'];
    }

    public function process()
    {
        $userService = new UserService();
        $user = $userService->getOrCreateUser($this->user);

        $sent = $user->potatoSent();
        $received = $user->potatoReceived();
        $leftToday = $user->potatoLeftToday();

        $this->slackClient->publishView(
            user: $this->user,
            view: [
                'type' => 'home',
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => '*Hey, <@' . $this->user . '>* :wave:',
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => 'I hope you\'re having a potastic day!',
                        ],
                    ],
                    [
                        'type' => 'divider',
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => '*Your Potato Stats*',
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => 'You have *' . $leftToday . '* :potato: left to gib today.',
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => 'You did gib *' . $sent . '* :potato: and received *' . $received . '* :potato: since you started potatoing *' . $user->created->format('M Y') . '*.',
                        ],
                    ],
                    [
                        'type' => 'divider',
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => '*Leaderboard*',
                        ],
                    ],
                    [
                        'type' => 'actions',
                        'elements' => [
                            [
                                'type' => 'button',
                                'text' => [
                                    'type' => 'plain_text',
                                    'text' => 'View Full Leaderboard',
                                ],
                                'url' => 'https://gibpotato.app',
                            ],
                        ],
                    ],
                ],
            ],
        );
    }
}
