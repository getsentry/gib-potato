<?php
declare(strict_types=1);

namespace App\Controller;

use App\Event\EventFactory;
use Cake\Controller\Controller;
use Cake\Http\Response;
use Sentry\SentrySdk;
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

        // Close session early to prevent blocking on HTTP client calls to Slack API.
        // Database session writes were causing the HTTP client to timeout after 30s.
        // This endpoint is a webhook that doesn't require session persistence.
        // See: GIBPOTATO-POTAL-11
        if ($this->request instanceof \Cake\Http\ServerRequest) {
            $session = $this->request->getSession();
            if ($session->started()) {
                $session->close();
            }
        }

        $startTimestamp = microtime(true);
        $event = EventFactory::createEvent($this->request->getData());
        $event->process();

        $span = SentrySdk::getCurrentHub()->getSpan();
        if ($span !== null) {
            $span->setData([
                'gibpotato.potatoes.event_processing_time' => microtime(true) - $startTimestamp,
                'gibpotato.potatoes.event_size' => mb_strlen(serialize($this->request->getData()), '8bit'),
                'gibpotato.event_type' => $event->type,
            ]);
            metrics()->distribution(
                'gibpotato.potatoes.event_processing_time',
                (float)microtime(true) - $startTimestamp,
                [
                    'gibpotato.event_type' => $event->type,
                ],
            );
            metrics()->gauge(
                'gibpotato.potatoes.event_processing_time',
                (float)microtime(true) - $startTimestamp,
                [
                    'gibpotato.event_type' => $event->type,
                ],
            );
            metrics()->distribution(
                'gibpotato.potatoes.event_size',
                (float)mb_strlen(serialize($this->request->getData()), '8bit'),
                [
                    'gibpotato.event_type' => $event->type,
                ],
            );
            metrics()->gauge(
                'gibpotato.potatoes.event_size',
                (float)mb_strlen(serialize($this->request->getData()), '8bit'),
                [
                    'gibpotato.event_type' => $event->type,
                ],
            );
        }

        return $this->response
            ->withType('json')
            ->withStatus(200);
    }
}
