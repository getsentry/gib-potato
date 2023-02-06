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
        $messagesTable = $this->fetchTable('Messages');
        $sentCountQuery = $messagesTable->find()
            ->select([
                'amount' => $messagesTable->find()->func()->sum('amount'),
            ])
            ->where([
                'sender_user_id = Users.id',
            ]);
        
        $reivedCountQuery = $messagesTable->find()
            ->select([
                'amount' => $messagesTable->find()->func()->sum('amount'),
            ])
            ->where([
                'receiver_user_id = Users.id',
            ]);


        $usersTable = $this->fetchTable('Users');

        $query = $usersTable->find();
        $query
            ->select([
                'sent_count' =>  $sentCountQuery,
                'received_count' =>  $reivedCountQuery,
            ])
            ->where([
                'Users.slack_is_bot' => false,
                'Users.status' => User::STATUS_ACTIVE,
                'Users.role !=' => User::ROLE_SERVICE,
            ])
            ->enableAutoFields(true);

        $range = $this->request->getQuery('range');
        if (!empty($range)) {
            switch ($range) {
                case 'week':
                    $query->andWhere([
                        'OR' => [
                            'MessagesSent.created >=' => new FrozenTime('1 week ago'),
                            'MessagesReceived.created >=' => new FrozenTime('1 week ago'),
                        ],
                    ]);
                    break;
                case 'month':
                    $query->andWhere([
                        'OR' => [
                            'MessagesSent.created >=' => new FrozenTime('1 month ago'),
                            'MessagesReceived.created >=' => new FrozenTime('1 month ago'),
                        ],
                    ]);
                    break;
                case 'year':
                    $query->andWhere([
                        'OR' => [
                            'MessagesSent.created >=' => new FrozenTime('1 year ago'),
                            'MessagesReceived.created >=' => new FrozenTime('1 year ago'),
                        ],
                    ]);
                    break;
            }
        }

        $order = $this->request->getQuery('order');
        if (!empty($order)) {
            switch ($order) {
                case 'sent':
                    $query->order(['sent_count' => 'DESC']);
                    break;
                case 'received':
                    $query->order(['received_count' => 'DESC']);
                    break;
            }
        }

        $users = $query
            ->all();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($users));
    }

    public function get(): Response
    {
        $messagesTable = $this->fetchTable('Messages');
        $sentCountQuery = $messagesTable->find()
            ->select([
                'amount' => $messagesTable->find()->func()->sum('amount'),
            ])
            ->where([
                'sender_user_id = Users.id',
            ]);
        
        $reivedCountQuery = $messagesTable->find()
            ->select([
                'amount' => $messagesTable->find()->func()->sum('amount'),
            ])
            ->where([
                'receiver_user_id = Users.id',
            ]);

        $usersTable = $this->fetchTable('Users');

        $user = $usersTable->find()
            ->select([
                'sent_count' =>  $sentCountQuery,
                'received_count' =>  $reivedCountQuery,
            ])
            ->where(['Users.id' => $this->Authentication->getIdentityData('id')])
            ->enableAutoFields(true)
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

    public function profile(): Response
    {
        $messagesTable = $this->fetchTable('Messages');
        $messages = $messagesTable->find()
            ->where([
                'OR' => [
                    'sender_user_id' => $this->Authentication->getIdentityData('id'),
                    'receiver_user_id' => $this->Authentication->getIdentityData('id'),
                ],
                'Messages.created >=' => new FrozenTime('30 days ago'),
            ])
            ->contain('SentUsers')
            ->contain('ReceivedUsers')
            ->order(['Messages.created' => 'DESC'])
            ->all();
        
        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($messages));
    }
}
