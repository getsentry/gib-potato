<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Http\Response;

/**
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class CollectionController extends ApiController
{
    /**
     * @return \Cake\Http\Response
     */
    public function get(): Response
    {
        $purchasesTable = $this->fetchTable('Purchases');
        $collection = $purchasesTable->find()
            ->where([
                'OR' => [
                    [
                        'user_id' => $this->Authentication->getIdentityData('id'),
                        'presentee_id IS' => null,
                    ],
                    [
                        'user_id !=' => $this->Authentication->getIdentityData('id'),
                        'presentee_id' => $this->Authentication->getIdentityData('id'),
                    ],
                ],
            ])
            ->all();

        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode($collection));
    }
}
