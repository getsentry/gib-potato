<?php
declare(strict_types=1);

namespace App\Database\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanContext;

class SentryQueryLogger extends AbstractLogger
{

    private $parentSpan = null;

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = []): void
    {
        $parentSpan = SentrySdk::getCurrentHub()->getSpan();

        if ($parentSpan === null) {
            return;
        }

        $loggedQuery = $context['query'];

        $context = new SpanContext();
        $context->setOp('db.sql.query');
        $context->setDescription($loggedQuery->query);
        $context->setStartTimestamp(microtime(true) - $loggedQuery->took / 1000);
        $context->setEndTimestamp($context->getStartTimestamp() + $loggedQuery->took / 1000);

        $span = $parentSpan->startChild($context);
    }
}