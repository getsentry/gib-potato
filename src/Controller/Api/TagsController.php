<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Http\Response;

/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class TagsController extends ApiController
{
    /**
     * @return \Cake\Http\Response
     */
    public function get(): Response
    {
        $taggedMessagesTable = $this->fetchTable('TaggedMessages');
        $messages = $this->fetchTable('Messages');
        
        $collection = $taggedMessagesTable->find();
        $collection->contain(['Users']);
        
        $collection->orderBy(['TaggedMessages.created' => 'DESC'])
            ->enableAutoFields(true)
            ->all();

        $collectedUsersInMessages = [];
        foreach ($collection as $taggedMessage) {
            $reactionCount = $messages->find()
                ->where(['permalink' => $taggedMessage->permalink])
                ->count();
            $taggedMessage->reaction_count = $reactionCount;
            // extract all user ids from message: "<@U042CECCR7A> has tagged you in a message"
            preg_match_all('/<@([A-Z0-9]+)>/', $taggedMessage->message, $matches);
            $collectedUsersInMessages += $matches[1];
            $taggedMessage->tagged_users = $matches[1];
        }

        $usersTable = $this->fetchTable('Users');
        $users = $usersTable->find()
            ->where(['Users.slack_user_id IN' => array_unique($collectedUsersInMessages)])
            ->all();

        foreach ($collection as $taggedMessage) {
            foreach ($users as $markedUsers) {
                // replace all user ids with user names
                $taggedMessage->message = str_replace(
                    "<@{$markedUsers->slack_user_id}>",
                    "<@{$markedUsers->slack_name}>",
                    $taggedMessage->message
                );
            }
        }
        
        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($collection));
    }
}
