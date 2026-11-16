<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\User;
use App\Utils\SentryTime;
use Cake\Http\Response;
use Cake\I18n\DateTime;

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
        $rangeTimeObject = match ($this->request->getQuery('range')) {
            'week' => new DateTime('1 week ago'),
            'month' => new DateTime('1 month ago'),
            'quarter' => SentryTime::getStartOfCurrentQuarter(),
            'year' => new DateTime('1 year ago'),
            default => new DateTime('2022-08-24 00:00:00'),
        };

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
                    ->groupBy('sender_user_id'),
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
                    ->groupBy('receiver_user_id'),
            ], [
                'Users.id = messages_received.user_id',
            ])
            ->where([
                'Users.slack_is_bot' => false,
                'Users.status' => User::STATUS_ACTIVE,
                'Users.role !=' => User::ROLE_SERVICE,
            ])
            ->enableAutoFields(true);

        $orderField = match ($this->request->getQuery('order')) {
            'sent' => 'sent_count',
            'received' => 'received_count',
            default => null,
        };
        if ($orderField !== null) {
            $query->orderBy([$orderField => $query->expr('DESC NULLS LAST')]);
        }

        $users = $query->all();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($users));
    }
}
