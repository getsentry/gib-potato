<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\User;
use Cake\Controller\Controller;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;

/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class ApiController extends Controller
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
        $rangeTimeObject = null;
        $range = $this->request->getQuery('range');
        switch ($range) {
            case 'week':
                $rangeTimeObject = new FrozenTime('1 week ago');
                break;
            case 'month':
                $rangeTimeObject = new FrozenTime('1 month ago');
                break;
            case 'year':
                $rangeTimeObject = new FrozenTime('1 year ago');
                break;
            default:
                $rangeTimeObject = new FrozenTime('2022-08-24 00:00:00');
        }

        $usersTable = $this->fetchTable('Users');
        $messagesTable = $this->fetchTable('Messages');

        $query = $usersTable->find();
        $query
            ->select([
                'sent_count' => 'messages_sent.sent_count',
                'received_count' => 'messages_received.received_count',
            ])
            ->leftJoin([
                'messages_sent' => $messagesTable->find()
                    ->select([
                        'user_id' => 'sender_user_id',
                        'sent_count' => $query->func()->sum('amount'),
                    ])
                    ->where([
                        'created >=' => $rangeTimeObject,
                    ])
                    ->group('sender_user_id'),
            ], [
                'Users.id = messages_sent.user_id',
            ])
            ->leftJoin([
                'messages_received' => $messagesTable->find()
                    ->select([
                        'user_id' => 'receiver_user_id',
                        'received_count' => $query->func()->sum('amount'),
                    ])
                    ->where([
                        'created >=' => $rangeTimeObject,
                    ])
                    ->group('receiver_user_id'),
            ], [
                'Users.id = messages_received.user_id',
            ])
            ->where([
                'Users.slack_is_bot' => false,
                'Users.status' => User::STATUS_ACTIVE,
                'Users.role !=' => User::ROLE_SERVICE,
            ])
            ->enableAutoFields(true);

        $order = $this->request->getQuery('order');
        switch ($order) {
            case 'sent':
                $query->order(['sent_count' => $query->newExpr('DESC NULLS LAST')]);
                break;
            case 'received':
                $query->order(['received_count' => $query->newExpr('DESC NULLS LAST')]);
                break;
        }

        $users = $query->all();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($users));
    }

    /**
     * @return \Cake\Http\Response
     */
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
                'sent_count' => $sentCountQuery,
                'received_count' => $reivedCountQuery,
            ])
            ->where(['Users.id' => $this->Authentication->getIdentityData('id')])
            ->contain('Progression')
            ->enableAutoFields(true)
            ->first();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($user));
    }

    /**
     * @return \Cake\Http\Response
     */
    public function edit(): Response
    {
        $usersTable = $this->fetchTable('Users');

        $user = $usersTable->find()
            ->where(['Users.id' => $this->Authentication->getIdentityData('id')])
            ->first();

        // Being super explicit here on purpose
        $user = $usersTable->patchEntity($user, [
            'notifications' => [
                'sent' => (bool)$this->request->getData('notifications.sent'),
                'received' => (bool)$this->request->getData('notifications.received'),
            ],
        ], [
            'accessibleFields' => [
                'notifications' => true,
            ],
        ]);
        $usersTable->saveOrFail($user);

        return $this->response
            ->withStatus(204);
    }

    /**
     * @return \Cake\Http\Response
     */
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
