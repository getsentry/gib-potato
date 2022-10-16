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
        $this->loadModel('Users');

        $query = $this->Users->find();
        $users = $query->select([
                'Users.id',
                'Users.slack_name',
                'Users.slack_picture',
                'send_count' => $query->func()->sum('MessagesSend.amount'),
                'received_count' => $query->func()->sum('MessagesReceived.amount'),
            ])
            ->distinct(['Users.id'])
            ->leftJoinWith('MessagesSend')
            ->leftJoinWith('MessagesReceived')
            ->order(['send_count' => 'DESC'])
            ->enableHydration(false)
            ->toArray();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($users));
    }
}
