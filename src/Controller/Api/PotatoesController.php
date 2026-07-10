<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Http\Response;

/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class PotatoesController extends ApiController
{
    /**
     * Paginated list of individual potato transactions (sent and received)
     * for the authenticated user. No message content, just the essentials
     * for building an over-time history in external systems.
     *
     * @return \Cake\Http\Response
     */
    public function list(): Response
    {
        $userId = $this->Authentication->getIdentityData('id');

        $messagesTable = $this->fetchTable('Messages');
        $query = $messagesTable->find()
            ->select([
                'Messages.id',
                'Messages.sender_user_id',
                'Messages.receiver_user_id',
                'Messages.amount',
                'Messages.created',
            ]);

        $direction = $this->request->getQuery('direction');
        switch ($direction) {
            case 'sent':
                $query->where(['Messages.sender_user_id' => $userId]);
                break;
            case 'received':
                $query->where(['Messages.receiver_user_id' => $userId]);
                break;
            default:
                $query->where([
                    'OR' => [
                        'Messages.sender_user_id' => $userId,
                        'Messages.receiver_user_id' => $userId,
                    ],
                ]);
        }

        $potatoes = $this->paginate($query, [
            'allowedParameters' => ['page'],
            'limit' => min(max((int)$this->request->getQuery('per_page', '100'), 1), 500),
            'maxLimit' => 500,
            'order' => ['Messages.created' => 'DESC'],
        ]);

        $items = [];
        foreach ($potatoes->items() as $message) {
            $sent = $message->sender_user_id === $userId;
            $items[] = [
                'id' => $message->id,
                'amount' => $message->amount,
                'direction' => $sent ? 'sent' : 'received',
                'user_id' => $sent ? $message->receiver_user_id : $message->sender_user_id,
                'created' => $message->created,
            ];
        }

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode([
                'potatoes' => $items,
                'pagination' => [
                    'page' => $potatoes->currentPage(),
                    'per_page' => $potatoes->perPage(),
                    'total' => $potatoes->totalCount(),
                    'total_pages' => $potatoes->pageCount(),
                    'has_next' => $potatoes->hasNextPage(),
                ],
            ]));
    }
}
