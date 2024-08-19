<?php
declare(strict_types=1);

namespace App\Event;

use App\Model\Entity\Poll;
use App\Service\PollService;
use App\Service\UserService;

class SlashCommandEvent extends AbstractEvent
{
    protected string $user;
    protected string $command;
    protected string $channel;
    protected string $text;
    protected string $triggerId;

    /**
     * Constructor
     *
     * @param array $event Event data.
     */
    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_SLASH_COMMAND;
        $this->user = $event['user'];
        $this->command = $event['command'];
        $this->channel = $event['channel'];
        $this->text = $event['text'];
        $this->triggerId = $event['trigger_id'];
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $this->slackClient->openView($this->triggerId, [
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
                [
                    'type'=> 'input',
                    'block_id' => 'poll-type',
                    'label' => [
                        'type' => 'plain_text',
                        'text' =>  'How do you want people to respond?',
                    ],
                    'element' => [
                        'type' => 'static_select',
                        'action_id' => 'poll-type-input',
                        'initial_option' => [
                            'text' => [
                                'type' => 'plain_text',
                                'text' =>  'Select multiple options',
                            ],
                            'value' => 'poll-type-multiple',
                        ],
                        'options' => [
                            [
                                'text' => [
                                    'type' => 'plain_text',
                                    'text' =>  'Select multiple options',
                                ],
                                'value' => 'poll-type-multiple',
                            ],
                            [
                                'text' => [
                                    'type' => 'plain_text',
                                    'text' =>  'Select one option',
                                ],
                                'value' => 'poll-type-single',
                            ],
                        ],
                    ],
                    'optional' => false,
                ],
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
        ]);

        return;

        $userService = new UserService();
        $pollService = new PollService();

        $pollsTable = $this->fetchTable('Polls');
        $pollOptionsTable = $this->fetchTable('PollOptions');

        preg_match_all('/(\“|\")(.*?)(\”|\")/', $this->text, $matches);

        $title = '';
        $options = [];

        foreach ($matches[2] as $key => $match) {
            if ($key === 0) {
                $title = $match;
                continue;
            }
            $options[] = $match;
        }

        if ($title === '') {
            $this->slackClient->postEphemeral(
                channel: $this->channel,
                user: $this->user,
                text: 'You need to specify a title. For example: `/gibopinion "Title" "Option 1" "Option 2" ...`',
            );

            return;
        }
        if (count($options) < 2) {
            $this->slackClient->postEphemeral(
                channel: $this->channel,
                user: $this->user,
                text: 'You need to specify at least two options.'
                    . 'For example: `/gibopinion "Title" "Option 1" "Option 2" ...`',
            );

            return;
        }
        if (count($options) > 9) {
            $this->slackClient->postEphemeral(
                channel: $this->channel,
                user: $this->user,
                text: 'You can specify a maximum of 9 options.',
            );

            return;
        }

        $poll = $pollsTable->newEntity([
            'user_id' => $userService->getOrCreateUser($this->user)->id,
            'title' => $title,
            'type' => Poll::TYPE_MULTIPLE,
            'status' => Poll::STATUS_ACTIVE,
            'anonymous' => str_contains($this->text, '--anonymous') ? true : false,
        ], [
            'accessibleFields' => [
                'user_id' => true,
                'title' => true,
                'type' => true,
                'status' => true,
                'anonymous' => true,
            ],
        ]);
        $pollsTable->saveOrFail($poll);

        foreach ($options as $option) {
            $pollOption = $pollOptionsTable->newEntity([
                'poll_id' => $poll->id,
                'title' => $option,
            ], [
                'accessibleFields' => [
                    'poll_id' => true,
                    'title' => true,
                ],
            ]);
            $pollOptionsTable->saveOrFail($pollOption);
        }

        $poll = $pollsTable->find()
            ->where(['Polls.id' => $poll->id])
            ->contain([
                'PollOptions' => [
                    'PollResponses' => [
                        'Users',
                    ],
                ],
                'Users',
            ])
            ->firstOrFail();

        $pollService->createPoll($poll, $this->channel);
    }
}
