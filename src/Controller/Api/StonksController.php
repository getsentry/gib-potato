<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Trade;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;

/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class StonksController extends ApiController
{
    /**
     * @return \Cake\Http\Response
     */
    public function list(): Response
    {
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
            ->contain(['Shares' => ['Stocks']])
            ->orderBy(['Trades.created' => 'DESC'])
            ->all();

        $sharesTable = $this->fetchTable('Shares');

        $response = [
            'trades' => $trades->map(function ($value) {
                return [
                        'id' => $value->id,
                        'symbol' => $value->share->stock->symbol,
                        'price' => $value->price,
                        'status' => $value->status,
                        'type' => $value->type,
                        'time' => $value->created->format('H:i'),
                    ];
            })->toList(),
            'portfilio' => $sharesTable->find()
                ->where([
                    'Shares.user_id IS' => $this->Authentication->getIdentity()->getIdentifier()
                ])
                ->contain(['Stocks' => [
                    'SharePrices' => function (SelectQuery $query) use ($stocks) {
                       return $query
                            ->orderBy(['SharePrices.id' => 'DESC']);
                    }]
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
            'stonks' => [],
        ];
        foreach ($stocks as $stock) {

            $sharePricesCollection = collection($stock->share_prices);
            $sharesCollection = collection($stock->shares);

            $startingPrice = $sharePricesCollection->first()->price;
            $sharePrice = $sharePricesCollection->last()->price;

            $labels = [];
            $time = new DateTime('2025-04-01 07:00:00');
            for ($i = 0; $i < 128; $i++) {
                $time = $time->modify('+15 minutes');
                $labels[] = $time->format('G');
            }

            $response['stonks'][] = [
                'id' => $stock->id,
                'symbol' => $stock->symbol,
                'description' => $stock->description,
                'share_price' => $sharePrice,
                'stock_info' => [
                    'amount' => $sharePrice - $startingPrice,
                    'open' => $startingPrice,
                    'high' => $sharePricesCollection->max('price')->price,
                    'low' => $sharePricesCollection->min('price')->price,
                    // @TODO This should be based on the trade volume
                    'volume' => $sharesCollection->count(),
                    'market_cap' => $sharesCollection->count() * $sharePrice,
                    'something' => $stock->volatility,
                ],
                'data' => [
                    // 'labels' => $sharePricesCollection->map(function ($value) {
                    //     return $value->created->format('H:i');
                    // })->toList(),
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
                        ]
                    ]
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
    public function order(): Response
    {
        $usersTable = $this->fetchTable('Users');
        /** @var \App\Model\Entity\User $user */
        $user = $usersTable->find()
            ->where(['Users.id' => $this->Authentication->getIdentity()->getIdentifier()])
            ->first();

        $sharePricesTable = $this->fetchTable('Shareprices');
        $sharePrice = $sharePricesTable->find()
            ->orderBy(['SharePrices.id' => 'DESC'])
            ->first()
            ->price;

        $sharesTable = $this->fetchTable('Shares');

        if ($this->request->getData('order_mode') === Trade::TYPE_BUY) {
            $shares = $sharesTable->find()
                ->where([
                    'Shares.stock_id IS' => $this->request->getData('stock_id'),
                    'Shares.user_id IS' => null,
                ])
                ->limit($this->request->getData('amount'));

            if ($shares->count() < $this->request->getData('amount')) {
                return $this->response
                    ->withStatus(400)
                    ->withType('json')
                    ->withStringBody(json_encode([
                        'error' => 'Not enough shares available 😥',
                    ]));
            }
            if ($sharePrice * $this->request->getData('amount') > $user->spendablePotato()) {
                return $this->response
                    ->withStatus(400)
                    ->withType('json')
                    ->withStringBody(json_encode([
                        'error' => 'Not enough potato to place this order 😥',
                    ]));
            }

            foreach ($shares as $share) {
                $share = $sharesTable->patchEntity($share, [
                    'user_id' => $user->id,
                ], [
                    'accessibleFields' => [
                        'user_id' => true,
                    ],
                ]);
    
                $tradesTable = $this->fetchTable('Trades');
                $trade = $tradesTable->newEntity([
                    'user_id' => $user->id,
                    'share_id' => $share->id,
                    'price' => $sharePrice,
                    'status' => Trade::STATUS_PENDING,
                    'type' => Trade::TYPE_BUY,
                ], [
                    'accessibleFields' => [
                        'user_id' => true,
                        'share_id' => true,
                        'price' => true,
                        'status' => true,
                        'type' => true,
                    ],
                ]);
    
                ConnectionManager::get('default')->begin();
                $sharesTable->saveOrFail($share);
                $tradesTable->saveOrFail($trade);
                ConnectionManager::get('default')->commit();
            }
        }

        if ($this->request->getData('order_mode') === Trade::TYPE_SELL) {
            $shares = $sharesTable->find()
                ->where([
                    'Shares.stock_id IS' => $this->request->getData('stock_id'),
                    'Shares.user_id IS' => $user->id,
                ])
                ->limit($this->request->getData('amount'));

            foreach ($shares as $share) {
                $share = $sharesTable->patchEntity($share, [
                    'user_id' => null,
                ], [
                    'accessibleFields' => [
                        'user_id' => true,
                    ],
                ]);
    
                $tradesTable = $this->fetchTable('Trades');
                $trade = $tradesTable->newEntity([
                    'user_id' => $user->id,
                    'share_id' => $share->id,
                    'price' => $sharePrice,
                    'status' => Trade::STATUS_PENDING,
                    'type' => Trade::TYPE_SELL,
                ], [
                    'accessibleFields' => [
                        'user_id' => true,
                        'share_id' => true,
                        'price' => true,
                        'status' => true,
                        'type' => true,
                    ],
                ]);
    
                ConnectionManager::get('default')->begin();
                $sharesTable->saveOrFail($share);
                $tradesTable->saveOrFail($trade);
                ConnectionManager::get('default')->commit();
            }
        }

        // if ($presentee !== null) {
        //     $blocks = [
        //         [
        //             'type' => 'section',
        //             'text' => [
        //                 'type' => 'mrkdwn',
        //                 'text' => "<@{$user->slack_user_id}> did buy a nice little present for "
        //                     . "<@{$presentee->slack_user_id}> 🎁😊",
        //             ],
        //         ],
        //         [
        //             'type' => 'section',
        //             'text' => [
        //                 'type' => 'mrkdwn',
        //                 'text' => "They got them *{$product->name}* 🚀",
        //             ],
        //         ],
        //         [
        //             'type' => 'section',
        //             'text' => [
        //                 'type' => 'mrkdwn',
        //                 'text' => "_{$this->request->getData('message')}_",
        //             ],
        //         ],
        //         [
        //             'type' => 'divider',
        //         ],
        //         [
        //             'type' => 'section',
        //             'text' => [
        //                 'type' => 'mrkdwn',
        //                 'text' => '<' . Router::url('/shop', true) . '|Gib a present to a fellow Sentaur yourself!>',
        //             ],
        //         ],
        //         [
        //             'type' => 'image',
        //             'image_url' => Router::url(str_replace('.svg', '.png', $product->image_link), true),
        //             'alt_text' => $product->name,
        //             'title' => [
        //                 'type' => 'plain_text',
        //                 'text' => $product->name,
        //             ],
        //         ],

        //     ];

            // $slackClient = new SlackClient();
            // $slackClient->postBlocks(
            //     channel: env('POTATO_CHANNEL'),
            //     blocks: json_encode($blocks),
            // );
        // }

        return $this->response
            ->withStatus(204);
    }
}
