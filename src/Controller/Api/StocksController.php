<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Trade;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Exception;
use function Cake\Collection\collection;

/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class StocksController extends ApiController
{
    /**
     * @return \Cake\Http\Response
     */
    public function list(): Response
    {
        $configTable = $this->fetchTable('Config');
        $config = $configTable->find()->firstOrFail();

        $stocksTable = $this->fetchTable('Stocks');
        $stocks = $stocksTable->find()
            ->contain('Shares', function (SelectQuery $query) {
                return $query
                    ->where(['Shares.user_id IS NOT' => null]);
            })
            ->contain('SharePrices', function (SelectQuery $query) {
                return $query
                    ->orderBy(['SharePrices.id' => 'ASC']);
            })
            ->orderBy(['Stocks.symbol' => 'ASC'])
            ->all();

        $tradesTable = $this->fetchTable('Trades');
        $trades = $tradesTable->find()
            ->where(['Trades.user_id' => $this->Authentication->getIdentity()->getIdentifier()])
            ->contain('Stocks')
            ->orderBy(['Trades.created' => 'DESC'])
            ->all();

        $sharesTable = $this->fetchTable('Shares');

        $response = [
            'trades' => $trades->map(function ($value) {
                return [
                    'id' => $value->id,
                    'symbol' => $value->stock->symbol,
                    'price' => $value->price,
                    'proposed_price' => $value->proposed_price,
                    'status' => $value->status,
                    'type' => $value->type,
                    'time' => $value->created->setTimezone(
                        $this->Authentication->getIdentity()->get('slack_time_zone'),
                    )->format('H:i'),
                ];
            })->toList(),
            'portfilio' => $sharesTable->find()
                ->where([
                    'Shares.user_id IS' => $this->Authentication->getIdentity()->getIdentifier(),
                ])
                ->contain(['Stocks' => [
                    'SharePrices' => function (SelectQuery $query) {
                        return $query
                            ->orderBy(['SharePrices.id' => 'DESC']);
                    }],
                ])
                ->all()
                ->groupBy('stock.symbol')
                ->map(function ($value) {
                    $value = collection($value);

                    return [
                        'symbol' => $value->first()->stock->symbol,
                        'count' => $value->count(),
                        'value' => $value->count() * collection($value->first()->stock->share_prices)->first()->price,
                    ];
                })->toList(),
            'stocks' => [],
            'market_open' => $config->market_open,
        ];
        foreach ($stocks as $stock) {
            $sharePricesCollection = collection($stock->share_prices);
            $sharesCollection = collection($stock->shares);

            $startingPrice = $sharePricesCollection->first()->price;
            $sharePrice = $sharePricesCollection->last()->price;

            $labels = [];
            $time = new DateTime('2025-04-01 06:15:00');
            for ($i = 0; $i < 288; $i++) {
                $time = $time->modify('+5 minutes')->setTimezone(
                    $this->Authentication->getIdentity()->get('slack_time_zone'),
                );
                $labels[] = $time->format('G');
            }

            $response['stocks'][] = [
                'id' => $stock->id,
                'symbol' => $stock->symbol,
                'description' => $stock->description,
                'share_price' => $sharePrice,
                'stock_info' => [
                    'amount' => $sharePrice - $startingPrice,
                    'open' => $startingPrice,
                    'high' => $sharePricesCollection->max('price')->price,
                    'low' => $sharePricesCollection->min('price')->price,
                    'volume' => $sharesCollection->count(),
                    'market_cap' => $sharesCollection->count() * $sharePrice,
                ],
                'data' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'data' => $sharePricesCollection->map(function ($value) {
                                return $value->price;
                            })->toList(),
                            'borderColor' => $sharePrice - $startingPrice > 0 ?
                                '#22c55e' : '#ef4444',
                            'backgroundColor' => $sharePrice - $startingPrice > 0 ?
                                'rgba(34, 197, 94, 0.1)' : 'rgba(239, 68, 68, 0.1)',
                        ],
                    ],
                ],
            ];
        }

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($response));
    }

    /**
     * @return \Cake\Http\Response
     */
    public function trades(): Response
    {
        $tradesTable = $this->fetchTable('Trades');
        $trades = $tradesTable->find()
            ->where([
                'status' => Trade::STATUS_PENDING,
            ])
            ->contain('Stocks')
            ->orderBy(['Trades.created' => 'DESC'])
            ->all();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($trades));
    }

    /**
     * @return \Cake\Http\Response
     */
    public function order(): Response
    {
        $configTable = $this->fetchTable('Config');
        $config = $configTable->find()->firstOrFail();
        if ($config->market_open === false) {
            throw new Exception('Market currently closed');
        }

        $stocksTable = $this->fetchTable('Stocks');
        $sharesTable = $this->fetchTable('Shares');

        $usersTable = $this->fetchTable('Users');
        /** @var \App\Model\Entity\User $user */
        $user = $usersTable->find()
            ->where(['Users.id' => $this->Authentication->getIdentity()->getIdentifier()])
            ->first();

        if ($this->request->getData('order_mode') === Trade::TYPE_BUY) {
            $stock = $stocksTable->find()
                ->contain('SharePrices', function (SelectQuery $query) {
                    return $query
                        ->orderBy(['SharePrices.id' => 'DESC']);
                })
                ->where(['id' => $this->request->getData('stock_id')])
                ->first();

            if ($stock->share_prices[0]->price * $this->request->getData('amount') > $user->spendablePotato()) {
                return $this->response
                    ->withStatus(400)
                    ->withType('json')
                    ->withStringBody(json_encode([
                        'error' => 'Not enough potato to place this order 😥',
                    ]));
            }

            $amountRequestData = (int)$this->request->getData('amount');
            for ($i = 0; $i < $amountRequestData; $i++) {
                $tradesTable = $this->fetchTable('Trades');
                $trade = $tradesTable->newEntity([
                    'user_id' => $user->id,
                    'stock_id' => $this->request->getData('stock_id'),
                    'proposed_price' => $this->request->getData('proposed_price'),
                    'status' => Trade::STATUS_PENDING,
                    'type' => Trade::TYPE_BUY,
                ], [
                    'accessibleFields' => [
                        'user_id' => true,
                        'stock_id' => true,
                        'proposed_price' => true,
                        'status' => true,
                        'type' => true,
                    ],
                ]);

                $tradesTable->saveOrFail($trade);
            }
        }

        if ($this->request->getData('order_mode') === Trade::TYPE_SELL) {
            $shares = $sharesTable->find()
                ->where([
                    'stock_id' => $this->request->getData('stock_id'),
                    'user_id IS' => $this->Authentication->getIdentity()->getIdentifier(),
                ]);

            if ($shares->count() < $this->request->getData('amount')) {
                return $this->response
                    ->withStatus(400)
                    ->withType('json')
                    ->withStringBody(json_encode([
                        'error' => "You don't own enough shares to place this order 😥",
                    ]));
            }

            $ownedShares = $shares->toArray();
            $amountRequestData = (int)$this->request->getData('amount');
            for ($i = 0; $i < $amountRequestData; $i++) {
                $tradesTable = $this->fetchTable('Trades');
                $trade = $tradesTable->newEntity([
                    'user_id' => $user->id,
                    'share_id' => $ownedShares[$i]->id,
                    'stock_id' => $this->request->getData('stock_id'),
                    'proposed_price' => $this->request->getData('proposed_price'),
                    'status' => Trade::STATUS_PENDING,
                    'type' => Trade::TYPE_SELL,
                ], [
                    'accessibleFields' => [
                        'user_id' => true,
                        'stock_id' => true,
                        'proposed_price' => true,
                        'status' => true,
                        'type' => true,
                    ],
                ]);
                $tradesTable->saveOrFail($trade);
            }
        }

        return $this->response
            ->withStatus(204);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function cancelOrder(): Response
    {
        $orderId = $this->request->getData('order_id');
        if (!$orderId) {
            return $this->response
                ->withStatus(400)
                ->withType('json')
                ->withStringBody(json_encode([
                    'error' => 'Order ID is required',
                ]));
        }

        // Check if the order ID is valid
        $tradesTable = $this->fetchTable('Trades');
        $trade = $tradesTable->find()
            ->where(['id' => $orderId])
            ->first();

        // Verify that the order belongs to the current user
        if ($trade->user_id !== $this->Authentication->getIdentity()->getIdentifier()) {
            return $this->response
                ->withStatus(403)
                ->withType('json')
                ->withStringBody(json_encode([
                    'error' => 'You are not authorized to cancel this order',
                ]));
        }

        $connection = $tradesTable->getConnection();
        return $connection->transactional(function () use ($tradesTable, $orderId) {
            $trade = $tradesTable->find()
                ->where([
                    'id' => $orderId,
                    'user_id' => $this->Authentication->getIdentity()->getIdentifier(),
                    'status' => Trade::STATUS_PENDING,
                ])
                ->epilog('FOR UPDATE')
                ->first();

            if (!$trade) {
                return $this->response
                    ->withStatus(404)
                    ->withType('json')
                    ->withStringBody(json_encode([
                        'error' => 'Order not found or cannot be cancelled',
                    ]));
            }

            $trade = $tradesTable->patchEntity($trade, [
                'status' => Trade::STATUS_CANCELLED,
            ]);
            $tradesTable->saveOrFail($trade);

            return $this->response
                ->withStatus(204);
        });
    }
}
