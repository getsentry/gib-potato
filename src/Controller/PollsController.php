<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Poll;
use App\Service\PollService;
use App\Service\UserService;
use Cake\Controller\Controller;
use Cake\Http\Response;

class PollsController extends Controller
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Authentication.Authentication');
    }

    /**
     * @return \Cake\Http\Response
     */
    public function list(): Response
    {
        $this->request->allowMethod('GET');

        $pollsTable = $this->fetchTable('Polls');

        $query = $pollsTable->find()
            ->contain([
                'PollOptions' => [
                    'PollResponses',
                ],
                'Users',
            ])
            ->orderByDesc('Polls.created')
            ->limit(5);

        $title = $this->request->getQuery('title');
        if ($title) {
            $query->where(['Polls.title LIKE' => "%{$title}%"]);
        }

        $status = $this->request->getQuery('status');
        if ($status) {
            $query->where(['Polls.status' => $status]);
        }

        $polls = $query->all()->map(function ($poll) {
            $options = [];
            foreach ($poll->poll_options as $option) {
                $options[] = [
                    'title' => $option->title,
                    'votes' => count($option->poll_responses),
                ];
            }

            return [
                'id' => $poll->id,
                'title' => $poll->title,
                'status' => $poll->status,
                'anonymous' => $poll->anonymous,
                'created_by' => $poll->user->slack_user_id ?? null,
                'created' => $poll->created?->toIso8601String(),
                'options' => $options,
            ];
        })->toArray();

        return $this->response
            ->withType('json')
            ->withStatus(200)
            ->withStringBody(json_encode(['polls' => array_values($polls)]));
    }

    /**
     * @return \Cake\Http\Response
     */
    public function create(): Response
    {
        $this->request->allowMethod('POST');

        $data = $this->request->getData();

        $title = $data['title'] ?? '';
        $options = $data['options'] ?? [];
        $channel = $data['channel'] ?? '';
        $userSlackId = $data['user_slack_id'] ?? '';
        $anonymous = (bool)($data['anonymous'] ?? false);

        if ($title === '') {
            return $this->response
                ->withType('json')
                ->withStatus(422)
                ->withStringBody(json_encode(['error' => 'Title is required.']));
        }

        if (count($options) < 2) {
            return $this->response
                ->withType('json')
                ->withStatus(422)
                ->withStringBody(json_encode(['error' => 'At least two options are required.']));
        }

        if (count($options) > 9) {
            return $this->response
                ->withType('json')
                ->withStatus(422)
                ->withStringBody(json_encode(['error' => 'A maximum of 9 options is allowed.']));
        }

        if ($channel === '') {
            return $this->response
                ->withType('json')
                ->withStatus(422)
                ->withStringBody(json_encode(['error' => 'Channel is required.']));
        }

        if ($userSlackId === '') {
            return $this->response
                ->withType('json')
                ->withStatus(422)
                ->withStringBody(json_encode(['error' => 'User Slack ID is required.']));
        }

        $userService = new UserService();
        $pollService = new PollService();

        $pollsTable = $this->fetchTable('Polls');
        $pollOptionsTable = $this->fetchTable('PollOptions');

        $poll = $pollsTable->newEntity([
            'user_id' => $userService->getOrCreateUser($userSlackId)->id,
            'title' => $title,
            'type' => Poll::TYPE_MULTIPLE,
            'status' => Poll::STATUS_ACTIVE,
            'anonymous' => $anonymous,
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

        $pollService->createPoll($poll, $channel);

        return $this->response
            ->withType('json')
            ->withStatus(201)
            ->withStringBody(json_encode([
                'id' => $poll->id,
                'title' => $poll->title,
            ]));
    }
}
