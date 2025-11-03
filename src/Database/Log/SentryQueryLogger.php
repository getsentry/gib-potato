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
}
