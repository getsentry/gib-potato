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
}
