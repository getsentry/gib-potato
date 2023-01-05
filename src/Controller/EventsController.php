<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Event\EventFactory;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\Utility\Security;

class EventsController extends Controller
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
    }

    public function index()
    {
        $event = EventFactory::createEvent($this->request->getData());
        $event->process();

        return $this->response
            ->withType('json')
            ->withStatus(200);
    }
}
