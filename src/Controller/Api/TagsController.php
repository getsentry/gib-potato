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
        $collection = $taggedMessagesTable->find()
            ->orderBy(['created' => 'DESC'])
            ->all();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($collection));
    }
}
