<?php
declare(strict_types=1);

namespace App\Controller;

use App\Event\EventFactory;
use Cake\Controller\Controller;

class EventsController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Authentication.Authentication');
    }

    public function index()
    {
        $this->request->allowMethod('POST');

        $event = EventFactory::createEvent($this->request->getData());
        $event->process();

        return $this->response
            ->withType('json')
            ->withStatus(200);
    }
}
