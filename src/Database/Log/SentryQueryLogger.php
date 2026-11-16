<?php
declare(strict_types=1);

namespace App\Database\Log;

use Psr\Log\AbstractLogger;
use Sentry\SentrySdk;
use Sentry\Tracing\Span;
use Sentry\Tracing\SpanContext;
use Sentry\Tracing\SpanStatus;
use Stringable;

class SentryQueryLogger extends AbstractLogger
{
    private array $parentSpanStack = [];
    private array $currentSpanStack = [];

    /**
     * @inheritDoc
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $parentSpan = SentrySdk::getCurrentHub()->getSpan();

        if ($parentSpan === null) {
            return;
        }

        $loggedQueryContext = $context['query']->getContext();
        if ($loggedQueryContext['query'] === 'BEGIN') {
            $spanContext = new SpanContext();
            $spanContext->setOp('db.transaction');
            $spanContext->setData([
                'db.system' => 'postgresql',
            ]);

            $this->pushSpan($parentSpan->startChild($spanContext));

            return;
        }

        if ($loggedQueryContext['query'] === 'COMMIT') {
            $span = $this->popSpan();

            if ($span !== null) {
                $span->finish();
                $span->setStatus(SpanStatus::ok());
            }

            return;
        }

        $spanContext = new SpanContext();
        $spanContext->setOp('db.sql.query');
        $spanContext->setData([
            'db.system' => 'postgresql',
        ]);
        $spanContext->setDescription($loggedQueryContext['query']);
        $spanContext->setStartTimestamp(microtime(true) - $loggedQueryContext['took'] / 1000);
        $spanContext->setEndTimestamp($spanContext->getStartTimestamp() + $loggedQueryContext['took'] / 1000);
        $parentSpan->startChild($spanContext);
    }

    /**
     * @param \Sentry\Tracing\Span $span The span.
     * @return void
     */
    private function pushSpan(Span $span): void
    {
        $hub = SentrySdk::getCurrentHub();

        $this->parentSpanStack[] = $hub->getSpan();

        $hub->setSpan($span);

        $this->currentSpanStack[] = $span;
    }

    /**
     * @return \Sentry\Tracing\Span|null
     */
    private function popSpan(): ?Span
    {
        if (count($this->currentSpanStack) === 0) {
            return null;
        }

        $parent = array_pop($this->parentSpanStack);

        SentrySdk::getCurrentHub()->setSpan($parent);

        return array_pop($this->currentSpanStack);
    }
}
