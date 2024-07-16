<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\User;
use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class UsersController extends ApiController
{
    /**
     * @return \Cake\Http\Response
     */
    public function list(): Response
    {
        $usersTable = $this->fetchTable('Users');

        $users = $usersTable->find()
            ->where([
                'Users.slack_is_bot' => false,
                'Users.status' => User::STATUS_ACTIVE,
                'Users.role !=' => User::ROLE_SERVICE,
            ])
            ->all();

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
            ->contain([
                'Progression',
            ])
            ->enableAutoFields(true)
            ->first();

        /** @var \App\Model\Entity\User $user */
        $user->spendable_count = $user->spendablePotato();
        $user->potato_left_today = $user->potatoLeftToday();
        $user->potato_reset_in_hours = $user->potatoResetInHours();
        $user->potato_reset_in_minutes = $user->potatoResetInMinutes();

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
                'too_good_to_go' => (bool)$this->request->getData('notifications.too_good_to_go'),
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
                'Messages.created >=' => new DateTime('30 days ago'),
            ])
            ->contain('SentUsers')
            ->contain('ReceivedUsers')
            ->orderBy(['Messages.created' => 'DESC'])
            ->all();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($messages));
    }
}
