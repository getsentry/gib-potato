<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\User;
use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;

class ApiController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Authentication.Authentication');
    }

    public function list(): Response
    {
        $usersTable = $this->fetchTable('Users');

        $query = $usersTable->find();
        $users = $query
            ->select([
                // 'sent_count' =>  $query->func()->sum('MessagesSent.amount'),
                'received_count' =>  $query->func()->sum('MessagesReceived.amount'),
            ])
            // ->leftJoinWith('MessagesSent')
            ->leftJoinWith('MessagesReceived')
            ->where([
                'Users.slack_is_bot' => false,
                'Users.status' => User::STATUS_ACTIVE,
                'Users.role !=' => User::ROLE_SERVICE,
            ])
            ->group(['Users.id'])
            ->having([
                'OR' => [
                    // 'sent_count >' => 0,
                    'received_count >' => 0
                ],
            ])
            ->enableAutoFields(true);

        $range = $this->request->getQuery('range');
        if (!empty($range)) {
            switch ($range) {
                case 'week':
                    $query->andWhere([
                        'OR' => [
                            // 'MessagesSent.created >=' => new FrozenTime('1 week ago'),
                            'MessagesReceived.created >=' => new FrozenTime('1 week ago'),
                        ],
                    ]);
                    break;
                case 'month':
                    $query->andWhere([
                        'OR' => [
                            // 'MessagesSent.created >=' => new FrozenTime('1 month ago'),
                            'MessagesReceived.created >=' => new FrozenTime('1 month ago'),
                        ],
                    ]);
                    break;
                case 'year':
                    $query->andWhere([
                        'OR' => [
                            // 'MessagesSent.created >=' => new FrozenTime('1 year ago'),
                            'MessagesReceived.created >=' => new FrozenTime('1 year ago'),
                        ],
                    ]);
                    break;
            }
        }

        $order = $this->request->getQuery('order');
        if (!empty($order)) {
            switch ($order) {
                // case 'sent':
                //     $query->order(['sent_count' => 'DESC']);
                //     break;
                case 'received':
                    $query->order(['received_count' => 'DESC']);
                    break;
            }
        }

        $query
            ->all();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($users));
    }

    public function get(): Response
    {
        $usersTable = $this->fetchTable('Users');

        $user = $usersTable->find()
            ->where(['Users.id' => $this->Authentication->getIdentityData('id')])
            ->first();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($user));
    }

    public function edit(): Response
    {
        $usersTable = $this->fetchTable('Users');

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
}
