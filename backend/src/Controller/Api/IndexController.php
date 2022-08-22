<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;

/**
 * Api/Index Controller
 *
 * @method \App\Model\Entity\Api/Index[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class IndexController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        return $this->response
            ->withStatus(200)
            ->withStringBody(json_encode([
                'message' => 'Gib Potato! 🥔'
            ]));
    }
}
