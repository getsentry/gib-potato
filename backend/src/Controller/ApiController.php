<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Database\Expression\QueryExpression;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\ORM\Query;

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
                'message' => 'GibPotato is very gut, ja!'
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
            // ->where(function (QueryExpression $exp, Query $query) {
            //     return $exp->isNotNull($query->func()->sum('MessagesReceived.amount'));
            // })
            ->order(['count' => 'DESC'])
            ->enableHydration(false)
            ->toArray();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($users));
    }

    public function logout()
    {
        $this->Authentication->logout();

        return $this->response
            ->withStatus(200)
            ->withType('json');
    }
}
