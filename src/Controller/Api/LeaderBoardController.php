<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\Trade;
use App\Model\Entity\User;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;
use function Cake\Collection\collection;

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
        $usersTable = $this->fetchTable('Users');
        $stockUsers = $usersTable->find()
            ->where([
                'Users.slack_is_bot' => false,
                'Users.status' => User::STATUS_ACTIVE,
                'Users.role !=' => User::ROLE_SERVICE,
            ])
            ->contain('Trades', function (SelectQuery $query) {
                return $query
                    ->where(['Trades.status' => Trade::STATUS_DONE]);
            })
            ->all();

        $stocks = collection($stockUsers)
            ->filter(function ($user) {
                return !empty($user->trades);
            })
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'stocks' => $user->getStocks(),
                    'slack_name' => $user->slack_name,
                    'slack_picture' => $user->slack_picture,

                ];
            })
            ->sortBy('stocks')
            ->toList();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode([
                'users' => [],
                'stocks' => $stocks,
            ]));
    }
}
