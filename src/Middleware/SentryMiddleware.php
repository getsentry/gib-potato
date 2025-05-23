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
use Sentry\SentrySdk;
use Sentry\Tracing\SpanContext;
use Sentry\Tracing\TransactionContext;
use Sentry\Tracing\TransactionSource;
use function microtime;
use function Sentry\logger;
use function Sentry\startTransaction;

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

        $transactionContext = TransactionContext::fromHeaders($sentryTraceHeader, $baggageHeader)
            ->setOp('http.server')
            ->setName($request->getMethod() . ' ' . $request->getUri()->getPath())
            ->setSource(TransactionSource::route())
            ->setStartTimestamp($requestStartTime);

        $transaction = startTransaction($transactionContext);

        SentrySdk::getCurrentHub()->setSpan($transaction);

        $spanContext = SpanContext::make()
            ->setOp('middleware.handle');
        $span = $transaction->startChild($spanContext);

        SentrySdk::getCurrentHub()->setSpan($span);

        $this->setupQueryLogging();

        $response = $handler->handle($request);

        $span->setHttpStatus($response->getStatusCode())
            ->finish();

        SentrySdk::getCurrentHub()->setSpan($transaction);

        // We don't want to trace 404 responses as they are not relevant.
        if ($response->getStatusCode() === 404) {
            $transaction->setSampled(false);
        } else {
            $transaction
                ->setHttpStatus($response->getStatusCode())
                ->setData([
                    'gibpotato.gcp.mem_peak_usage' => memory_get_peak_usage(false),
                ]);
        }

        EventManager::instance()->on(
            'Server.terminate',
            function (Event $event) use ($transaction): void {
                $transaction->finish();
                logger()->flush();
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
