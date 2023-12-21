<?php
declare(strict_types=1);

namespace App\Controller;

use App\Event\EventFactory;
use Cake\Controller\Controller;
use Cake\Http\Response;
use Sentry\Metrics\MetricsUnit;
use function Sentry\metrics;

class EventsController extends Controller
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Authentication.Authentication');
    }

    /**
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        $this->request->allowMethod('POST');

        $event = EventFactory::createEvent($this->request->getData());
        $event->process();

        metrics()->distribution(
            key: 'gibpotato.potatoes.event_size',
            value: mb_strlen(serialize($this->request->getData()), '8bit'),
            unit: MetricsUnit::byte(),
            tags: [
                'event_type' => $event->getType(),
            ],
        );

        return $this->response
            ->withType('json')
            ->withStatus(200);
    }
}
