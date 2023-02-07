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

    public function products() {
        $productsTable = $this->fetchTable('Products');
        $products = $productsTable->find()
            ->all();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($products));
    }

    public function purchase() {
        $productsTable = $this->fetchTable('Products');
        $product = $productsTable->find()
            ->where(['Products.id' => $this->request->getData('product_id')])
            ->first();

        if ($product->stock < 1) {
            return $this->response
                ->withStatus(400)
                ->withType('json')
                ->withStringBody(json_encode([
                    'error' => 'Product out of stock 😥',
                ]));
        }

        $purchasesTable = $this->fetchTable('Purchases');
        $purchase = $purchasesTable->newEntity([
            'user_id' => $this->Authentication->getIdentityData('id'),
            'name' => $product->name,
            'description' => $product->description,
            'image_link' => $product->image_link,
            'price' => $product->price,
        ], [
            'accessibleFields' => [
                'user_id' => true,
                'name' => true,
                'description' => true,
                'image_link' => true,
                'price' => true,
            ],
        ]);
        $purchasesTable->saveOrFail($purchase);

        $productsTable->patchEntity($product, [
            'stock' => $product->stock - 1,
        ], [
            'accessibleFields' => [
                'stock' => true,
            ],
        ]);
        $productsTable->saveOrFail($product);

        return $this->response
            ->withStatus(204);
    }
}
