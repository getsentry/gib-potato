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
        $sentryTraceHeader = $request->getHeaderLine('sentry-trace');
        $baggageHeader = $request->getHeaderLine('baggage');

        $transactionContext = TransactionContext::fromHeaders($sentryTraceHeader, $baggageHeader);

        $requestStartTime = $request->getServerParams()['REQUEST_TIME_FLOAT'] ?? \microtime(true);

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

        $span->setHttpStatus($response->getStatusCode());
        $span->finish();

        SentrySdk::getCurrentHub()->setSpan($transaction);

        $transaction->setHttpStatus($response->getStatusCode());
        $transaction->finish();

        return $response;
    }

    public function setupQueryLogging()
    {
        $logger = new SentryQueryLogger();

        $connection = ConnectionManager::get('default');
        $connection->enableQueryLogging();
        $connection->setLogger($logger);
    }
}
