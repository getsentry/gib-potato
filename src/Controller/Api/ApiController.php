<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Controller\Controller;

class ApiController extends Controller
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Authentication.Authentication');
    }
}
