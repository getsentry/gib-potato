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
        $users = $query->select([
                'Users.id',
                'Users.slack_name',
                'Users.slack_picture',
                'Users.slack_is_bot',
                'sent_count' => $query->func()->sum('MessagesSend.amount'),
                'received_count' => $query->func()->sum('MessagesReceived.amount'),
            ])
            ->distinct(['Users.id'])
            ->where(['Users.slack_is_bot' => false])
            ->leftJoinWith('MessagesSend')
            ->leftJoinWith('MessagesReceived')
            ->order(['sent_count' => 'DESC'])
            ->enableHydration(false)
            ->toArray();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($users));
    }
}
