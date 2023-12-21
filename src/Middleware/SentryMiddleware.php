<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Database\Log\SentryQueryLogger;
use Cake\Datasource\ConnectionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanContext;
use Sentry\Tracing\TransactionContext;
use Sentry\Tracing\TransactionSource;
use function microtime;
use function Sentry\metrics;
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

        $sentryTraceHeader = $request->getHeaderLine('sentry-trace');
        $baggageHeader = $request->getHeaderLine('baggage');

        $transactionContext = TransactionContext::fromHeaders($sentryTraceHeader, $baggageHeader);

        $requestStartTime = $request->getServerParams()['REQUEST_TIME_FLOAT'] ?? microtime(true);

        $transactionContext->setOp('http.server');
        $transactionContext->setName($request->getMethod() . ' ' . $request->getUri()->getPath());
        $transactionContext->setSource(TransactionSource::route());
        $transactionContext->setStartTimestamp($requestStartTime);

        $transaction = startTransaction($transactionContext);

        SentrySdk::getCurrentHub()->setSpan($transaction);

        $spanContext = new SpanContext();
        $spanContext->setOp('middleware.handle');
        $span = $transaction->startChild($spanContext);

        SentrySdk::getCurrentHub()->setSpan($span);

        $this->setupQueryLogging();

        $response = $handler->handle($request);
        // We don't want to trace 404 responses as they are not relevant for performance monitoring.
        if ($response->getStatusCode() === 404) {
            $transaction->setSampled(false);
        }

        $span->setHttpStatus($response->getStatusCode());
        $span->finish();

        SentrySdk::getCurrentHub()->setSpan($transaction);

        $transaction->setHttpStatus($response->getStatusCode());
        $transaction->finish();

        register_shutdown_function(static fn () => metrics()->flush());

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
