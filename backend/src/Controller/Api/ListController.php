<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;

/**
 * @method \App\Model\Entity\Api/Index[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ListController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        sleep(0.5); // simulate slow api
        return $this->response
            ->withStatus(200)
            ->withType('json')
            ->withStringBody(json_encode([
                [
                    'id' => 1,
                    'full_name' => 'Krys',
                    'avatar_url' => 'https://',
                    'count' => 12,
                ],
                [
                  'id' => 2,
                  'full_name' => 'Michi',
                    'avatar_url' => 'https://',
                    'count' => 3,
                ],
                [
                  'id' => 3,
                  'full_name' => 'Gino',
                    'avatar_url' => 'https://',
                    'count' => 5,
                ],
                [
                  'id' => 4,
                  'full_name' => 'Tobias',
                    'avatar_url' => 'https://',
                    'count' => 9,
                ],
            ]));
    }
}
