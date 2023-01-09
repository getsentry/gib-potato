<?php
declare(strict_types=1);

namespace App\Database\Log;

use Psr\Log\AbstractLogger;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanContext;
use Stringable;

class SentryQueryLogger extends AbstractLogger
{
    private $parentSpan = null;

    /**
     * @inheritDoc
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $parentSpan = SentrySdk::getCurrentHub()->getSpan();

        if ($this->parentSpan === null) {
            $this->parentSpan = $parentSpan;
        }

        $loggedQuery = $context['query'];

        $context = new SpanContext();
        $context->setOp('db.sql.query');
        $context->setDescription($loggedQuery->query);
        $context->setStartTimestamp(microtime(true) - $loggedQuery->took / 1000);
        $context->setEndTimestamp($context->getStartTimestamp() + $loggedQuery->took / 1000);
        $this->parentSpan->startChild($context);
    }
}
