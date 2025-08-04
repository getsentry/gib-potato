<?php

namespace App\Http\Controllers;

use App\Events\EventFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sentry\State\Scope;

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

        // Add Sentry performance monitoring
        if (function_exists('\Sentry\withScope')) {
            \Sentry\withScope(function (Scope $scope) use ($event, $startTimestamp, $request) {
                $scope->setContext('performance', [
                    'gibpotato.potatoes.event_processing_time' => microtime(true) - $startTimestamp,
                    'gibpotato.potatoes.event_size' => mb_strlen(serialize($request->all()), '8bit'),
                    'gibpotato.event_type' => $event->getType(),
                ]);
            });
        }

        return response()->json(['ok' => true]);
    }
}