<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

class HomeController extends AppController
{
    /**
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->response = $this->response
            ->withHeader('Document-Policy', 'js-profiling');
    }
}
