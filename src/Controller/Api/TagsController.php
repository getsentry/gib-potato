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

        foreach ($collection as $taggedMessage) {
            $reactionCount = $messages->find()
                ->where(['permalink' => $taggedMessage->permalink])
                ->count();
            $taggedMessage->reaction_count = $reactionCount;
        }
        
        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($collection));
    }
}
