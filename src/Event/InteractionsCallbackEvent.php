<?php
declare(strict_types=1);

namespace App\Event;

use App\Model\Entity\Poll;
use App\Model\Table\PollOptionsTable;
use App\Model\Table\PollResponsesTable;
use App\Model\Table\PollsTable;
use App\Service\PollService;
use App\Service\UserService;
use Sentry\State\Scope;

use function Sentry\captureMessage;
use function Sentry\withScope;

class InteractionsCallbackEvent extends AbstractEvent
{
    protected string $user;
    protected string $actionId;
    protected string $responseUrl;
    protected string $triggerId;
    protected ?string $value;
    protected ?string $selectOptionValue;
    protected ?array $responseUrls;
    protected ?array $view;

    protected UserService $userService;
    protected PollService $pollService;

    protected PollsTable $pollsTable;
    protected PollOptionsTable $pollOptionsTable;
    protected PollResponsesTable $pollResponsesTable;

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
        $this->triggerId = $event['trigger_id'];

        $this->value = $event['value'] ?? null;
        $this->selectOptionValue = $event['select_option_value'] ?? null;
        $this->responseUrls = $event['response_urls'] ?? null;
        $this->view = $event['view'] ?? null;

        $this->userService = new UserService();
        $this->pollService = new PollService();

        $this->pollsTable = $this->fetchTable('Polls');
        $this->pollOptionsTable = $this->fetchTable('PollOptions');
        $this->pollResponsesTable = $this->fetchTable('PollResponses');
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        if ($this->view !== null) {
            $this->create();

            return;
        }

        if ($this->value === 'poll-vote') {
            $this->vote();

            return;
        }

        if ($this->selectOptionValue === 'poll-close') {
            $this->close();

            return;
        }

        if ($this->selectOptionValue === 'poll-reopen') {
            $this->reopen();

            return;
        }

        if ($this->selectOptionValue === 'poll-delete') {
            $this->delete();

            return;
        }
    }

    protected function create(): void
    {
        $title = $this->view['state']['values']['poll-title']['poll-title-input']['value'];
        $channel = $this->responseUrls[0]['channel_id'];

        $options = [];
        foreach ($this->view['state']['values'] as $key => $value) {
            if (str_starts_with($key, 'option-')) {
                $options[] = $value['option-input']['value'];
            }
        }

        $poll = $this->pollsTable->newEntity([
            'user_id' => $this->userService->getOrCreateUser($this->user)->id,
            'title' => $title,
            'type' => Poll::TYPE_MULTIPLE,
            'status' => Poll::STATUS_ACTIVE,
            'anonymous' => false,
        ], [
            'accessibleFields' => [
                'user_id' => true,
                'title' => true,
                'type' => true,
                'status' => true,
                'anonymous' => true,
            ],
        ]);
        $this->pollsTable->saveOrFail($poll);

        foreach ($options as $option) {
            $pollOption = $this->pollOptionsTable->newEntity([
                'poll_id' => $poll->id,
                'title' => $option,
            ], [
                'accessibleFields' => [
                    'poll_id' => true,
                    'title' => true,
                ],
            ]);
            $this->pollOptionsTable->saveOrFail($pollOption);
        }

        $poll = $this->pollsTable->find()
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

        $this->pollService->createPoll($poll, $channel);
    }

    /**
     * @return void
     */
    protected function vote(): void
    {
        $pollOption = $this->pollOptionsTable->find()
            ->where([
                'id' => $this->actionId,
            ])
            ->firstOrFail();

        $existingPollResponse = $this->pollResponsesTable->find()
            ->where([
                'user_id' => $this->userService->getOrCreateUser($this->user)->id,
                'poll_option_id' => $pollOption->id,
            ])
            ->first();

        if (empty($existingPollResponse)) {
            $pollResponse = $this->pollResponsesTable->newEntity([
                'user_id' => $this->userService->getOrCreateUser($this->user)->id,
                'poll_option_id' => $pollOption->id,
            ], [
                'accessibleFields' => [
                    'user_id' => true,
                    'poll_option_id' => true,
                ],
            ]);
            $this->pollResponsesTable->saveOrFail($pollResponse);
        } else {
            $this->pollResponsesTable->deleteOrFail($existingPollResponse);
        }

        $poll = $this->pollsTable->find()
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

        $this->pollService->updatePoll($poll, $this->responseUrl);
    }

    /**
     * @return void
     */
    protected function close(): void
    {
        $poll = $this->pollsTable->find()
            ->where(['id' => $this->actionId])
            ->firstOrFail();

        if ($poll->user_id !== $this->userService->getOrCreateUser($this->user)->id) {
            $this->pollService->triggerPollModal($this->triggerId);

            return;
        }

        $poll = $this->pollsTable->patchEntity($poll, [
            'status' => Poll::STATUS_CLOSED,
        ], [
            'accessibleFields' => [
                'status' => true,
            ],
        ]);
        $this->pollsTable->saveOrFail($poll);

        $poll = $this->pollsTable->find()
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

        $this->pollService->updatePoll($poll, $this->responseUrl);
    }

    /**
     * @return void
     */
    protected function reopen(): void
    {
        $poll = $this->pollsTable->find()
            ->where(['id' => $this->actionId])
            ->firstOrFail();

        if ($poll->user_id !== $this->userService->getOrCreateUser($this->user)->id) {
            $this->pollService->triggerPollModal($this->triggerId);

            return;
        }

        $poll = $this->pollsTable->patchEntity($poll, [
            'status' => Poll::STATUS_ACTIVE,
        ], [
            'accessibleFields' => [
                'status' => true,
            ],
        ]);
        $this->pollsTable->saveOrFail($poll);

        $poll = $this->pollsTable->find()
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

        $this->pollService->updatePoll($poll, $this->responseUrl);
    }

    /**
     * @return void
     */
    protected function delete(): void
    {
        $poll = $this->pollsTable->find()
            ->where(['id' => $this->actionId])
            ->firstOrFail();

        if ($poll->user_id !== $this->userService->getOrCreateUser($this->user)->id) {
            $this->pollService->triggerPollModal($this->triggerId);

            return;
        }

        $this->pollsTable->deleteOrFail($poll);

        $this->pollService->deletePoll($poll, $this->responseUrl);
    }
}
