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
                withScope(function ($scope) use ($json, $blocks, $responseUrl) {
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
                withScope(function ($scope) use ($json, $responseUrl) {
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
    public function getPollModalView(): array
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
