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
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
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
        ], [
            'accessibleFields' => [
                'user_id' => true,
                'title' => true,
                'type' => true,
                'status' => true,
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
