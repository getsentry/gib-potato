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
     * @return \Cake\Http\Response
     */
    public function list(): Response
    {
        $messagesTable = $this->fetchTable('Messages');

        $sent = $messagesTable->find()
            ->select([
                'amount' => 'Messages.amount',
                'recipient' => 'Messages.receiver_user_id',
                'created' => 'Messages.created',
            ])
            ->where(['Messages.sender_user_id' => $this->Authentication->getIdentity()->getIdentifier()])
            ->orderBy(['Messages.created' => 'DESC'])
            ->disableHydration()
            ->all();

        $received = $messagesTable->find()
            ->select([
                'amount' => 'Messages.amount',
                'sender' => 'Messages.sender_user_id',
                'created' => 'Messages.created',
            ])
            ->where(['Messages.receiver_user_id' => $this->Authentication->getIdentity()->getIdentifier()])
            ->orderBy(['Messages.created' => 'DESC'])
            ->disableHydration()
            ->all();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode([
                'sent' => $sent,
                'received' => $received,
            ]));
    }
}
