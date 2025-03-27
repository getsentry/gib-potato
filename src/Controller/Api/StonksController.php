<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Trade;
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

        if ($this->request->getData('order_mode') === Trade::TYPE_BUY) {
            for ($i = 0; $i < (int) $this->request->getData('amount'); $i++) {    
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
            for ($i = 0; $i < (int) $this->request->getData('amount'); $i++) {  
                $tradesTable = $this->fetchTable('Trades');
                $trade = $tradesTable->newEntity([
                    'user_id' => $user->id,
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
