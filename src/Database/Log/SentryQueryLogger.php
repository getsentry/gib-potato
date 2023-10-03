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
        // @TODO(michi) Re-enable once it's fixed upstream
        return;

        $parentSpan = SentrySdk::getCurrentHub()->getSpan();

        if ($parentSpan === null) {
            return;
        }

        $loggedQuery = $context['query'];

        if ($loggedQuery->query === 'BEGIN') {
            $context = new SpanContext();
            $context->setOp('db.transaction');
            $context->setData([
                'db.system' => 'postgresql',
            ]);

            $this->pushSpan($parentSpan->startChild($context));

            return;
        }

        if ($loggedQuery->query === 'COMMIT') {
            $span = $this->popSpan();

            if ($span !== null) {
                $span->finish();
                $span->setStatus(SpanStatus::ok());
            }

            return;
        }

        $context = new SpanContext();
        $context->setOp('db.sql.query');
        $context->setData([
            'db.system' => 'postgresql',
        ]);
        $context->setDescription($loggedQuery->query);
        $context->setStartTimestamp(microtime(true) - $loggedQuery->took / 1000);
        $context->setEndTimestamp($context->getStartTimestamp() + $loggedQuery->took / 1000);
        $parentSpan->startChild($context);
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
