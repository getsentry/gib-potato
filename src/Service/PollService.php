<?php
declare(strict_types=1);

namespace App\Service;

use App\Http\Client;
use App\Http\SlackClient;
use App\Model\Entity\Poll;
use Cake\ORM\Locator\LocatorAwareTrait;
use function Sentry\captureMessage;
use function Sentry\withScope;

class PollService
{
    use LocatorAwareTrait;

    protected Client $client;
    protected SlackClient $slackClient;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->slackClient = new SlackClient();
    }

    /**
     * @param \App\Model\Entity\Poll $poll The poll.
     * @param string $channel The channel.
     * @return void
     */
    public function createPoll(Poll $poll, string $channel): void
    {
        $blocks = $this->getPollBlocks($poll);

        $this->slackClient->postBlocks(
            channel: $channel,
            blocks: json_encode($blocks),
        );
    }

    /**
     * @param \App\Model\Entity\Poll $poll The poll.
     * @param string $responseUrl The response URL.
     * @return void
     */
    public function updatePoll(Poll $poll, string $responseUrl): void
    {
        $blocks = $this->getPollBlocks($poll);

        $client = new Client();
        $response = $client->post($responseUrl, json_encode([
            'replace_original' => true,
            'blocks' => $blocks,
        ]));
        if ($response->isSuccess()) {
            $json = $response->getJson();

            if ($json['ok'] === false) {
                withScope(function ($scope) use ($json, $blocks, $responseUrl): void {
                    $scope->setExtras([
                        'blocks' => $blocks,
                        'slack_response' => $json,
                        'response_url' => $responseUrl,
                    ]);
                    captureMessage('Slack API error: RESPONSE_URL');
                });
            }
        }
    }

    /**
     * @param \App\Model\Entity\Poll $poll The poll.
     * @param string $responseUrl The response URL.
     * @return void
     */
    public function deletePoll(Poll $poll, string $responseUrl): void
    {
        $client = new Client();
        $response = $client->post($responseUrl, json_encode([
            'delete_original' => true,
        ]));
        if ($response->isSuccess()) {
            $json = $response->getJson();

            if ($json['ok'] === false) {
                withScope(function ($scope) use ($json, $responseUrl): void {
                    $scope->setExtras([
                        'slack_response' => $json,
                        'response_url' => $responseUrl,
                    ]);
                    captureMessage('Slack API error: RESPONSE_URL');
                });
            }
        }
    }

    /**
     * @param string $triggerId The trigger ID.
     * @return void
     */
    public function triggerPollView(string $triggerId, int $optionsCount = 2): void
    {
        $view = $this->getPollView($optionsCount);

        $this->slackClient->openView(
            triggerId: $triggerId,
            view: $view,
        );
    }

    /**
     * @param string $triggerId The trigger ID.
     * @return void
     */
    public function triggerPollModal(string $triggerId): void
    {
        $view = $this->getPollModalView();

        $this->slackClient->openView(
            triggerId: $triggerId,
            view: $view,
        );
    }

    /**
     * @param \App\Model\Entity\Poll $poll The poll.
     * @return array
     */
    protected function getPollBlocks(Poll $poll): array
    {
        $blocks = [];

        if ($poll->status === Poll::STATUS_CLOSED) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '📣 *The results are in!*',
                ],
            ];
            $blocks[] = [
                'type' => 'divider',
            ];
        }

        if ($poll->status === Poll::STATUS_ACTIVE) {
            $options = [
                [
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '🔒 Close Poll',
                    ],
                    'value' => 'poll-close',
                ],
                [
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '❌ Delete Poll',
                    ],
                    'value' => 'poll-delete',
                ],
            ];
        } else {
            $options = [
                [
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '🔓 Reopen Poll',
                    ],
                    'value' => 'poll-reopen',
                ],
                [
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '❌ Delete Poll',
                    ],
                    'value' => 'poll-delete',
                ],
            ];
        }

        if ($poll->anonymous === true) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '🥷 Anonymous',
                ],
            ];
        }

        $blocks[] = [
            'type' => 'section',
            'block_id' => 'poll-actions',
            'text' => [
                'type' => 'mrkdwn',
                'text' => "*{$poll->title}*",
            ],
            'accessory' => [
                'action_id' => (string)$poll->id,
                'type' => 'overflow',
                'options' => $options,
            ],
        ];

        foreach ($poll->poll_options as $index => $option) {
            $responseCount = count($option->poll_responses);
            if ($responseCount > 0) {
                if ($poll->anonymous === false) {
                    $users = [];
                    foreach ($option->poll_responses as $response) {
                        $users[] = "<@{$response->user->slack_user_id}>";
                    }
                    $users = implode(' ', $users);

                    $title = "{$option->title} `{$responseCount}`\n{$users}";
                } else {
                    $title = "{$option->title} `{$responseCount}`";
                }
            } else {
                $title = $option->title;
            }

            $emoji = $this->getEmojiForIndex($index);

            if ($poll->status === Poll::STATUS_ACTIVE) {
                $blocks[] = [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "{$emoji} {$title}",
                    ],
                    'accessory' => [
                        'type' => 'button',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => "{$emoji}",
                            'emoji' => true,
                        ],
                        'value' => 'poll-vote',
                        'action_id' => (string)$option->id,
                    ],
                ];
            } else {
                $blocks[] = [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "{$emoji} {$title}",
                    ],
                ];
            }
        }

        $context = "Created by <@{$poll->user->slack_user_id}> with /gibopinion";
        if ($poll->status === Poll::STATUS_CLOSED) {
            $context .= '   🔒 This poll is now closed.';
        }
        $blocks[] = [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'mrkdwn',
                    'text' => $context,
                ],
            ],
        ];

        return $blocks;
    }

    /**
     * @return array 
     */
    protected function getPollView(int $optionsCount): array
    {
        return [
            'type' => 'modal',
            'title' => [
                'type' => 'plain_text',
                'text' => 'Create Poll',
            ],
            'blocks' => [
                [
                    'type' => 'input',
                    'block_id' => 'poll-channel',
                    'label' => [
                        'type' => 'plain_text',
                        'text' =>  'Send your poll to',
                    ],
                    'element' => [
                        'action_id' => 'poll-channel-input',
                        'type' => 'conversations_select',
                        'response_url_enabled' => true,
                        'default_to_current_conversation' => true,
                    ],
                ],
                [
                    'type'=> 'input',
                    'block_id' => 'poll-title',
                    'label' => [
                        'type' => 'plain_text',
                        'text' =>  'Question or Topic',
                    ],
                    'element' => [
                        'type' => 'plain_text_input',
                        'action_id' => 'poll-title-input',
                        'placeholder' => [
                            'type' => 'plain_text',
                            'text' => 'The meaning of life is?',
                        ],
                        'multiline' => false,
                    ],
                    'optional' => false,
                ],
                // [
                //     'type'=> 'input',
                //     'block_id' => 'poll-type',
                //     'label' => [
                //         'type' => 'plain_text',
                //         'text' =>  'How do you want people to respond?',
                //     ],
                //     'element' => [
                //         'type' => 'static_select',
                //         'action_id' => 'poll-type-input',
                //         'initial_option' => [
                //             'text' => [
                //                 'type' => 'plain_text',
                //                 'text' =>  'Select multiple options',
                //             ],
                //             'value' => 'poll-type-multiple',
                //         ],
                //         'options' => [
                //             [
                //                 'text' => [
                //                     'type' => 'plain_text',
                //                     'text' =>  'Select multiple options',
                //                 ],
                //                 'value' => 'poll-type-multiple',
                //             ],
                //             [
                //                 'text' => [
                //                     'type' => 'plain_text',
                //                     'text' =>  'Select one option',
                //                 ],
                //                 'value' => 'poll-type-single',
                //             ],
                //         ],
                //     ],
                //     'optional' => false,
                // ],
                [
                    'type'=> 'input',
                    'block_id' => 'option-1',
                    'label' => [
                        'type' => 'plain_text',
                        'text' =>  'Option 1',
                    ],
                    'element' => [
                        'type' => 'plain_text_input',
                        'action_id' => 'option-input',
                        'placeholder' => [
                            'type' => 'plain_text',
                            'text' => 'First Option',
                        ],
                        'multiline' => false,
                    ],
                    'optional' => false,
                ],
                [
                    'type'=> 'input',
                    'block_id' => 'option-2',
                    'label' => [
                        'type' => 'plain_text',
                        'text' =>  'Option 2',
                    ],
                    'element' => [
                        'type' => 'plain_text_input',
                        'action_id' => 'option-input',
                        'placeholder' => [
                            'type' => 'plain_text',
                            'text' => 'Another option',
                        ],
                        'multiline' => false,
                    ],
                    'optional' => true,
                ],
                [
                    'type'=> 'actions',
                    'elements' => [
                        [
                            'type' => 'button',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'Add another option',
                            ],
                            'action_id' => 'poll-add-option'
                        ],
                    ],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'section',
                    'block_id' => 'poll-settings',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => '*Settings* (optional)',
                    ],
                    'accessory' => [
                        'type' => 'checkboxes',
                        'action_id' => 'poll-settings-input',
                        'options' => [
                            [
                                'text' => [
                                    'type' => 'mrkdwn',
                                    'text' => '*Make responses anonymous*',
                                ],
                                'value' => 'poll-settings-anonymous',
                            ],
                            // [
                            //     'text' => [
                            //         'type' => 'mrkdwn',
                            //         'text' => '*Allow others to add options*',
                            //     ],
                            //     'value' => 'poll-settings-allow-new-options',
                            // ],
                        ],
                    ],
                ]
            ],
            'close' => [
                'type' => 'plain_text',
                'text' => 'Cancel',
            ],
            'submit' => [
                'type' => 'plain_text',
                'text' => 'Publish Poll',
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getPollModalView(): array
    {
        return [
            'type' => 'modal',
            'title' => [
                'type' => 'plain_text',
                'text' => 'Nope, nope, nope 🫣',
            ],
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => 'You are not the poll creator 🚫',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param int $index The option index.
     * @return string
     */
    protected function getEmojiForIndex(int $index): string
    {
        $emojis = [
            ':one:',
            ':two:',
            ':three:',
            ':four:',
            ':five:',
            ':six:',
            ':seven:',
            ':eight:',
            ':nine:',
        ];

        return $emojis[$index];
    }
}
