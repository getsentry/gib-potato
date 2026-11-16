<?php
declare(strict_types=1);

namespace App\Controller;

class HomeController extends AppController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $this->response = $this->response
            ->withHeader('Document-Policy', 'js-profiling');
    }
}
