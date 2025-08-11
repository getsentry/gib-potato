<?php

namespace App\Http\Controllers;

use App\Events\EventFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sentry\SentrySdk;

class EventsController extends Controller
{
    /**
     * Handle incoming Slack events from potal service
     *
     * @param Request $request
     * @param EventFactory $eventFactory
     * @return JsonResponse
     */
    public function handle(Request $request, EventFactory $eventFactory): JsonResponse
    {
        $startTimestamp = microtime(true);
        
        // Create and process the event
        $event = $eventFactory->create($request->all());
        $event->process();

        $span = SentrySdk::getCurrentHub()->getSpan();
        if ($span !== null) {
            $span->setData([
                'gibpotato.potatoes.event_processing_time' => microtime(true) - $startTimestamp,
                'gibpotato.potatoes.event_size' => mb_strlen(serialize($request->getData()), '8bit'),
                'gibpotato.event_type' => $event->type,
            ]);
        }

        return response()->json();
    }
}