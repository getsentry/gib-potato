<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Database\Log\SentryQueryLogger;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sentry\Metrics\MetricsUnit;
use Sentry\Tracing\Spans\Span;
use function microtime;
use function Sentry\metrics;

/**
 * Sentry middleware
 */
class SentryMiddleware implements MiddlewareInterface
{
    /**
     * Process method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // We don't want to trace OPTIONS and HEAD requests as they are not relevant for performance monitoring.
        if (in_array($request->getMethod(), ['OPTIONS', 'HEAD'], true)) {
            return $handler->handle($request);
        }

        $requestStartTime = $request->getServerParams()['REQUEST_TIME_FLOAT'] ?? microtime(true);

        $sentryTraceHeader = $request->getHeaderLine('sentry-trace');
        $baggageHeader = $request->getHeaderLine('baggage');

        $segmentSpan = Span::makeFromTrace($sentryTraceHeader, $baggageHeader)
            ->setAttribiute('sentry.op', 'http.server')
            ->setName($request->getMethod() . ' ' . $request->getUri()->getPath())
            ->setStartTimeUnixNanosetStartTime($requestStartTime)
            ->start();

        $span = Span::make()
            ->setAttribiute('sentry.op', 'middleware.handle')
            ->start();

        $this->setupQueryLogging();

        $response = $handler->handle($request);
        // We don't want to trace 404 responses as they are not relevant for performance monitoring.
        // if ($response->getStatusCode() === 404) {
        //     $transaction->setSampled(false);
        // }

        $span->finish();
        $segmentSpan
            ->setAttribiute('http.response.status_code', (string)$response->getStatusCode())
            ->finish();

        metrics()->distribution(
            key: 'gibpotato.gcp.mem_peak_usage',
            value: memory_get_peak_usage(false),
            unit: MetricsUnit::byte(),
        );

        EventManager::instance()->on(
            'Server.terminate',
            function (Event $event): void {
                metrics()->flush();
            },
        );

        return $response;
    }

    /**
     * @return void
     */
    public function setupQueryLogging(): void
    {
        $logger = new SentryQueryLogger();

        $connection = ConnectionManager::get('default');
        $connection->getDriver()->setLogger($logger);
    }
}
