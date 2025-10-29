<?php
declare(strict_types=1);

namespace App\Database\Log;

use Psr\Log\AbstractLogger;
use Sentry\SentrySdk;
use Sentry\Tracing\Spans\Spans;
use Stringable;
use function Sentry\startSpan;

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
            startSpan('db.transaction');

//            $this->pushSpan($dbTransactionSpan);

            return;
        }

        if ($loggedQueryContext['query'] === 'COMMIT') {
            SentrySdk::getCurrentHub()->getSpan()?->finish();
            Spans::getInstance()->flush();

            return;
        }

        $span = startSpan('db.sql.query');
        $span->setAttributes([
            'db.system' => 'postgresql',
            'sentry.op' => 'db.sql.query',
            'sentry.description' => $loggedQueryContext['query'],
        ]);

        $span->setStartTimestamp(microtime(true) - $loggedQueryContext['took'] / 1000);
        $span->finish();
        $span->setEndTimestamp($span->getStartTimestamp() + $loggedQueryContext['took'] / 1000);
    }

//    /**
//     * @param \Sentry\Tracing\Spans\Span $span The span.
//     * @return void
//     */
//    private function pushSpan(Span $span): void
//    {
//        $hub = SentrySdk::getCurrentHub();
//
//        $this->parentSpanStack[] = $hub->getSpan();
//
//        $hub->setSpan($span);
//
//        $this->currentSpanStack[] = $span;
//    }
//
//    /**
//     * @return \Sentry\Tracing\Spans\Span|null
//     */
//    private function popSpan(): ?Span
//    {
//        if (count($this->currentSpanStack) === 0) {
//            return null;
//        }
//
//        $parent = array_pop($this->parentSpanStack);
//
//        SentrySdk::getCurrentHub()->setSpan($parent);
//
//        return array_pop($this->currentSpanStack);
//    }
}
