<?php
declare(strict_types=1);

namespace App\Database\Log;

use Psr\Log\AbstractLogger;
use Sentry\SentrySdk;
use Sentry\Tracing\Spans\Span;
use Stringable;

class SentryQueryLogger extends AbstractLogger
{
    // private array $parentSpanStack = [];
    // private array $currentSpanStack = [];

    /**
     * @inheritDoc
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $parentSpan = SentrySdk::getCurrentHub()->getSpan();

        if ($parentSpan === null) {
            return;
        }

        $dbTransactionSpan = null;
        $loggedQueryContext = $context['query']->getContext();
        if ($loggedQueryContext['query'] === 'BEGIN') {
            $dbTransactionSpan = Span::make()
                ->setAttribiute('sentry.op', 'db.transaction')
                ->setAttribiute('db.system', 'postgresql');

            return;
        }

        if ($loggedQueryContext['query'] === 'COMMIT') {
            if ($dbTransactionSpan !== null) {
                $dbTransactionSpan->finish();
            }

            return;
        }

        $startTime = microtime(true) - $loggedQueryContext['took'] / 1_000;
        $endTime = $startTime + $loggedQueryContext['took'] / 1_000;

        Span::make()
            ->setName($loggedQueryContext['query'])
            ->setAttribiute('sentry.op', 'db.sql.query')
            ->setAttribiute('db.system', 'postgresql')
            ->setStartTimeUnixNanosetStartTime($startTime)
            ->setEndTimeUnixNanosetStartTime($endTime)
            ->finish();
    }

    // /**
    //  * @param \Sentry\Tracing\Span $span The span.
    //  * @return void
    //  */
    // private function pushSpan(Span $span): void
    // {
    //     $hub = SentrySdk::getCurrentHub();

    //     $this->parentSpanStack[] = $hub->getSpan();

    //     $hub->setSpan($span);

    //     $this->currentSpanStack[] = $span;
    // }

    // /**
    //  * @return \Sentry\Tracing\Span|null
    //  */
    // private function popSpan(): ?Span
    // {
    //     if (count($this->currentSpanStack) === 0) {
    //         return null;
    //     }

    //     $parent = array_pop($this->parentSpanStack);

    //     SentrySdk::getCurrentHub()->setSpan($parent);

    //     return array_pop($this->currentSpanStack);
    // }
}
