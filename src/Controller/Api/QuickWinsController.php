<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Http\Response;

/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class QuickWinsController extends ApiController
{
    /**
     * @return \Cake\Http\Response
     */
    public function get(): Response
    {
        $quickWinsTable = $this->fetchTable('QuickWins');

        $quickWins = $quickWinsTable->find()
            ->contain('Users')
            ->orderBy(['QuickWins.created' => 'DESC'])
            ->all();

        $collectedUsersInMessages = [];
        foreach ($quickWins as $quickWin) {
            // extract all user ids from message: "<@U042CECCR7A> has tagged you in a message"
            preg_match_all('/<@([A-Z0-9]+)>/', $quickWin->message, $matches);
            foreach($matches[1] as $match) {
                $collectedUsersInMessages[] = $match;
            }
        }

        $collectedUsersInMessages = array_unique($collectedUsersInMessages);

        $usersTable = $this->fetchTable('Users');
        $users = $usersTable->find()
            ->where([
                'slack_user_id IN' => !empty($collectedUsersInMessages) ?
                    $collectedUsersInMessages : [null],
            ])
            ->all();

        foreach ($quickWins as $quickWin) {
            foreach ($users as $user) {
                // replace all user ids with user names
                $quickWin->message = str_replace(
                    "<@{$user->slack_user_id}>",
                    "<@{$user->slack_name}>",
                    $quickWin->message
                );
            }
        }

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($quickWins));
    }
}
