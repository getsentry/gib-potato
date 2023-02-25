<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\User;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;

/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class LeaderBoardController extends ApiController
{
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

        $range = $this->request->getQuery('range');
        if (!empty($range)) {
            switch ($range) {
                case 'week':
                    $sentCountQuery->where([
                        'created >=' => new FrozenTime('1 week ago'),
                    ]);
                    $reivedCountQuery->where([
                        'created >=' => new FrozenTime('1 week ago'),
                    ]);
                    break;
                case 'month':
                    $sentCountQuery->where([
                        'created >=' => new FrozenTime('1 month ago'),
                    ]);
                    $reivedCountQuery->where([
                        'created >=' => new FrozenTime('1 month ago'),
                    ]);
                    break;
                case 'year':
                    $sentCountQuery->where([
                        'created >=' => new FrozenTime('1 year ago'),
                    ]);
                    $reivedCountQuery->where([
                        'created >=' => new FrozenTime('1 year ago'),
                    ]);
                    break;
            }
        }

        $usersTable = $this->fetchTable('Users');

        $query = $usersTable->find();
        $query
            ->select([
                'sent_count' => $sentCountQuery,
                'received_count' => $reivedCountQuery,
            ])
            ->leftJoinWith('MessagesSent')
            ->leftJoinWith('MessagesReceived')
            ->where([
                'Users.slack_is_bot' => false,
                'Users.status' => User::STATUS_ACTIVE,
                'Users.role !=' => User::ROLE_SERVICE,
            ])
            ->group(['Users.id'])
            ->enableAutoFields(true);

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
}
