<?php
declare(strict_types=1);

namespace App\Controller\Api;

/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class ShopController extends ApiController
{
    /**
     * @return \Cake\Http\Response
     */
    public function products()
    {
        $productsTable = $this->fetchTable('Products');
        $products = $productsTable->find()
            ->all();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($products));
    }

    /**
     * @return \Cake\Http\Response
     */
    public function purchase()
    {
        $usersTable = $this->fetchTable('Users');
        /** @var \App\Model\Entity\User $user */
        $user = $usersTable->find()
            ->where(['Users.id' => $this->Authentication->getIdentity()->getIdentifier()])
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

        $purchasesTable = $this->fetchTable('Purchases');
        $purchase = $purchasesTable->newEntity([
            'user_id' => $user->id,
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
