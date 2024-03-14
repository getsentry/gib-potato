<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * QuickWins Controller
 *
 * @property \App\Model\Table\QuickWinsTable $QuickWins
 */
class QuickWinsController extends AppController
{
    /**
     * View method
     *
     * @param string|null $id Quick Win id.
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view($id = null)
    {
        $quickWinsTable = $this->fetchTable('QuickWins');

        $quickWin = $quickWinsTable->find()
            ->contain('Users')
            ->where(['QuickWins.id' => $id])
            ->firstOrFail();

        $collectedUsersInMessage = [];
        preg_match_all('/<@([A-Z0-9]+)>/', $quickWin->message, $matches);
        foreach($matches[1] as $match) {
            $collectedUsersInMessage[] = $match;
        }

        $collectedUsersInMessage = array_unique($collectedUsersInMessage);

        $usersTable = $this->fetchTable('Users');
        $users = $usersTable->find()
            ->where([
                'slack_user_id IN' => !empty($collectedUsersInMessage) ?
                    $collectedUsersInMessage : [null],
            ])
            ->all();

        foreach ($users as $user) {
            // replace all user ids with user names
            $quickWin->message = str_replace(
                "<@{$user->slack_user_id}>",
                "<@{$user->slack_name}>",
                $quickWin->message
            );
        }

        $quickWin->message = str_replace(
            ':potato:',
            '🥔',
            $quickWin->message
        );

        $this->set(compact('quickWin'));
    }
}
