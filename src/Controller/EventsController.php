<?php
declare(strict_types=1);

namespace App\Controller;

use App\Event\EventFactory;
use Cake\Controller\Controller;
use Cake\Http\Response;
use Sentry\Metrics\MetricsUnit;
use Sentry\SentrySdk;
use Sentry\State\Scope;

use function Sentry\captureMessage;
use function Sentry\metrics;
use function Sentry\withScope;

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

        $startTimestamp = microtime(true);
        $event = EventFactory::createEvent($this->request->getData());
        $event->process();

        withScope(function(Scope $scope) {
            $scope->setContext('request_payload', $this->request->getData());
            captureMessage('Event Payload');
        });

        metrics()->distribution(
            key: 'gibpotato.potatoes.event_processing_time',
            value: microtime(true) - $startTimestamp,
            unit: MetricsUnit::second(),
            tags: [
                'event_type' => $event->getType(),
            ],
        );

        metrics()->distribution(
            key: 'gibpotato.potatoes.event_size',
            value: mb_strlen(serialize($this->request->getData()), '8bit'),
            unit: MetricsUnit::byte(),
            tags: [
                'event_type' => $event->getType(),
            ],
        );

        $span = SentrySdk::getCurrentHub()->getSpan();
        if ($span !== null) {
            $span->setData([
                'gibpotato.potatoes.event_processing_time' => microtime(true) - $startTimestamp,
                'gibpotato.potatoes.event_size' => mb_strlen(serialize($this->request->getData()), '8bit'),
            ]);
        }

        return $this->response
            ->withType('json')
            ->withStatus(200);
    }
}
