<?php
declare(strict_types=1);

namespace App\Event;

use App\Service\PollService;
use App\Service\UserService;

class InteractionsCallbackEvent extends AbstractEvent
{
    protected string $user;
    protected string $actionId;
    protected string $responseUrl;

    /**
     * Constructor
     *
     * @param array $event Event data.
     */
    public function __construct(array $event)
    {
        parent::__construct();

        $this->type = self::TYPE_INTERACTIONS_CALLBACK;
        $this->user = $event['user'];
        $this->actionId = $event['action_id'];
        $this->responseUrl = $event['response_url'];
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
        $pollResponsesTable = $this->fetchTable('PollResponses');

        $pollOption = $pollOptionsTable->find()
            ->where([
                'id' => $this->actionId,
            ])
            ->firstOrFail();

        $existingPollResponse = $pollResponsesTable->find()
            ->where([
                'user_id' => $userService->getOrCreateUser($this->user)->id,
                'poll_option_id' => $pollOption->id,
            ])
            ->first();

        if (empty($existingPollResponse)) {
            $pollResponse = $pollResponsesTable->newEntity([
                'user_id' => $userService->getOrCreateUser($this->user)->id,
                'poll_option_id' => $pollOption->id,
            ], [
                'accessibleFields' => [
                    'user_id' => true,
                    'poll_option_id' => true,
                ],
            ]);
            $pollResponsesTable->saveOrFail($pollResponse);
        } else {
            $pollResponsesTable->deleteOrFail($existingPollResponse);
        }

        $poll = $pollsTable->find()
            ->where(['Polls.id' => $pollOption->poll_id])
            ->contain([
                'PollOptions' => [
                    'PollResponses' => [
                        'Users',
                    ],
                ],
                'Users',
            ])
            ->firstOrFail();

        $pollService->updatePoll($poll, $this->responseUrl);
    }
}
