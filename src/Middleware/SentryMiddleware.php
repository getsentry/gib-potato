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
use Sentry\Tracing\Spans\Spans;
use function microtime;
use function Sentry\logger;
use function Sentry\setPropagationContext;
use function Sentry\startSpan;

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

        setPropagationContext($sentryTraceHeader, $baggageHeader);

        $segmentSpan = startSpan($request->getMethod() . ' ' . $request->getUri()->getPath());
        $segmentSpan->setAttribute('sentry.op', 'http.server');
        $segmentSpan->setStartTimestamp($requestStartTime);

        $span = startSpan('middleware.handle');

        $this->setupQueryLogging();

        $response = $handler->handle($request);

        $span->finish();
        $segmentSpan
            ->setAttribute('http.response_code', (string)$response->getStatusCode())
            ->setAttribute('gibpotato.gcp.mem_peak_usage', memory_get_peak_usage(false))
            ->finish();

        EventManager::instance()->on(
            'Server.terminate',
            function (Event $event): void {
                logger()->flush();
                Spans::getInstance()->flush();
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
