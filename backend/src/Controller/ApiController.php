<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;

class ApiController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['index']);
    }

    public function index(): Response
    {
        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode([
                'message' => 'GibPotato! 🥔'
            ]));
    }

    public function user(): Response
    {
        $this->loadModel('Users');

        $query = $this->Users->find();
        $user = $query->select([
                'Users.id',
                'Users.slack_name',
                'Users.slack_picture',
                'count' => $query->func()->sum('MessagesReceived.amount'),
            ])
            ->where(['Users.id' => $this->Authentication->getIdentityData('id')])
            ->leftJoinWith('MessagesReceived')
            ->enableHydration(false)
            ->first();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($user));
    }

    public function users(): Response
    {
        usleep(500); // simulate slow api

        $this->loadModel('Users');

        $query = $this->Users->find();
        $users = $query->select([
                'Users.id',
                'Users.slack_name',
                'Users.slack_picture',
                'count' => $query->func()->sum('MessagesReceived.amount'),
            ])
            ->distinct(['Users.id'])
            ->leftJoinWith('MessagesReceived')
            ->enableHydration(false)
            ->toArray();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($users));
    }

    public function messages(): Response
    {
        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode([
                [
                    'id' => '1',
                    'sender_id' => '1',
                    'sender_name' => 'Krys',
                    'sender_picture' => 'https://',
                    'receiver_id' => '2',
                    'receiver_name' => 'Michi',
                    'receiver_picture' => 'https://',
                    'amount' => 5,
                ],
                [
                    'id' => '2',
                    'sender_id' => '3',
                    'sender_name' => 'Gino',
                    'sender_picture' => 'https://',
                    'receiver_id' => '4',
                    'receiver_name' => 'Tobias',
                    'receiver_picture' => 'https://',
                    'amount' => 2,
                ],
            ]));
    }
}
