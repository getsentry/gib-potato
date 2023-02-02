<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;

class ApiController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Authentication.Authentication');
    }

    public function users(): Response
    {
        $usersTable = $this->fetchTable('Users');

        $query = $usersTable->find();
        $users = $query
            ->select([
                'sent_count' => $query->func()->sum('MessagesSend.amount'),
                'received_count' => $query->func()->sum('MessagesReceived.amount'),
            ])
            ->leftJoinWith('MessagesSend')
            ->leftJoinWith('MessagesReceived')
            ->where(['Users.slack_is_bot' => false])
            ->group(['Users.id'])
            ->order(['received_count' => 'DESC'])
            ->enableAutoFields(true)
            ->all();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($users));
    }

    public function user(): Response
    {
        $usersTable = $this->fetchTable('Users');

        if ($this->request->is('GET')) {
            $user = $usersTable->find()
            ->where(['Users.id' => $this->Authentication->getIdentityData('id')])
            ->first();

            return $this->response
                ->withStatus(200)
                ->withType('json')
                ->withStringBody(json_encode($user));
        }
        if ($this->request->is('POST')) {
            $user = $usersTable->find()
                ->where(['Users.id' => $this->Authentication->getIdentityData('id')])
                ->first();

            // Being super explicit here on purpose
            $user = $usersTable->patchEntity($user, [
                'notifications' => [
                    'sent' => (bool) $this->request->getData('notifications.sent'),
                    'received' => (bool) $this->request->getData('notifications.received'),
                ]
            ], [
                'accessibleFields' => [
                    'notifications' => true,
                ],
            ]);
            $usersTable->saveOrFail($user);

            return $this->response
                ->withStatus(204);
        }

        return $this->response
            ->withStatus(405)
            ->withType('json')
            ->withStringBody(json_encode([
                'error' => 'Method not allowed',
            ]));
    }
}
