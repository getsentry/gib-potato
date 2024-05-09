<?php
declare(strict_types=1);

namespace App\Event;

use App\Service\UserService;

class AppHomeOpenedEvent extends AbstractEvent
{
    protected string $user;
    protected string $tab;
    protected string $timestamp;

    /**
     * Constructor
     *
     * @param array $event Event data.
     */
    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_APP_HOME_OPENED;
        $this->user = $event['user'];
        $this->tab = $event['tab'];
        $this->eventTimestamp = $event['event_timestamp'];
    }

    /**
     * @inheritDoc
     */
    public function process(): void
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
                            'text' => 'You have *' . $leftToday . '* :potato: left to gib today.' .
                                sprintf(
                                    ' Your potato do reset in *%s hours* and *%s minutes*.',
                                    $user->potatoResetInHours(),
                                    $user->potatoResetInMinutes(),
                                ),
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => 'You did gib *' . $sent . '* :potato: and did receive *' . $received .
                                '* :potato: since you started potatoing *' . $user->created->format('M j, Y') . '*.',
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => (function () use ($user): string {
                                if ($user->progression === null) {
                                    return 'Your current potato level is `Potato Novice`';
                                }

                                return 'Your current potato level is `Level ' . $user->progression->id . ' (' .
                                    $user->progression->name . ')`';
                            })(),
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
