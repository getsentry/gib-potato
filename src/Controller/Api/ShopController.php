<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Http\SlackClient;
use Cake\Http\Response;
use Cake\Routing\Router;

/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class ShopController extends ApiController
{
    /**
     * @return \Cake\Http\Response
     */
    public function products(): Response
    {
        $productsTable = $this->fetchTable('Products');
        $products = $productsTable->find()
            ->orderBy(['name' => 'ASC'])
            ->all();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($products));
    }

    /**
     * @return \Cake\Http\Response
     */
    public function purchase(): Response
    {
        $usersTable = $this->fetchTable('Users');
        /** @var \App\Model\Entity\User $user */
        $user = $usersTable->find()
            ->where(['Users.id' => $this->Authentication->getIdentity()->getIdentifier()])
            ->first();

        $presentee = $usersTable->find()
            ->where(['Users.id IS' => $this->request->getData('presentee_id')])
            ->first();

        $productsTable = $this->fetchTable('Products');
        /** @var \App\Model\Entity\Product $product */
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
        if ($product->price > $user->spendablePotato()) {
            return $this->response
                ->withStatus(400)
                ->withType('json')
                ->withStringBody(json_encode([
                    'error' => 'Not enough potato to buy 😥',
                ]));
        }
        if ($this->request->getData('purchase_mode') === 'someone-else') {
            if ($presentee === null) {
                return $this->response
                    ->withStatus(400)
                    ->withType('json')
                    ->withStringBody(json_encode([
                        'error' => 'Select someone 🧐',
                    ]));
            }
            if (empty($this->request->getData('message'))) {
                return $this->response
                    ->withStatus(400)
                    ->withType('json')
                    ->withStringBody(json_encode([
                        'error' => 'Add a message 🧐',
                    ]));
            }
        }

        $purchasesTable = $this->fetchTable('Purchases');
        $purchase = $purchasesTable->newEntity([
            'user_id' => $user->id,
            'presentee_id' => $presentee->id ?? null,
            'name' => $product->name,
            'description' => $product->description,
            'image_link' => $product->image_link,
            'price' => $product->price,
            'message' => $this->request->getData('message'),
        ], [
            'accessibleFields' => [
                'user_id' => true,
                'presentee_id' => true,
                'name' => true,
                'description' => true,
                'image_link' => true,
                'price' => true,
                'message' => true,
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

        if ($presentee !== null) {
            $blocks = [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "<@{$user->slack_user_id}> did buy a nice little present for "
                            . "<@{$presentee->slack_user_id}> 🎁😊",
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "They got them *{$product->name}* 🚀",
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "_{$this->request->getData('message')}_",
                    ],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => '<' . Router::url('/shop', true) . '|Gib a present to a fellow Sentaur yourself!>',
                    ],
                ],
                [
                    'type' => 'image',
                    'image_url' => Router::url(str_replace('.svg', '.png', $product->image_link), true),
                    'alt_text' => $product->name,
                    'title' => [
                        'type' => 'plain_text',
                        'text' => $product->name,
                    ],
                ],

            ];

            $slackClient = new SlackClient();
            $slackClient->postBlocks(
                channel: env('POTATO_CHANNEL'),
                blocks: json_encode($blocks),
            );
        }

        return $this->response
            ->withStatus(204);
    }
}
