<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Http\Response;
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

        $response = [];
        foreach ($stocks as $stock) {
            $startingPrice = collection($stock->share_prices)->first()->price;
            $sharePrice = collection($stock->share_prices)->last()->price;

            $response[] = [
                'symbol' => $stock->symbol,
                'description' => $stock->description,
                'share_price' => $sharePrice,
                'stock_info' => [
                    'amount' => $sharePrice - $startingPrice,
                    'open' => $startingPrice,
                    'high' => collection($stock->share_prices)->max('price')->price,
                    'low' => collection($stock->share_prices)->min('price')->price,
                    'market_cap' => collection($stock->shares)->count() * $sharePrice,
                    'volume' => collection($stock->shares)->count(),
                ],
                'data' => [
                    'labels' => collection($stock->share_prices)->map(function ($value) {
                        return $value->created->format('H:i');
                    })->toList(),
                    'datasets' => [
                        [
                            'data' => collection($stock->share_prices)->map(function ($value) {
                                return $value->price;
                            })->toList(),
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
        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode([]));
    }
}
