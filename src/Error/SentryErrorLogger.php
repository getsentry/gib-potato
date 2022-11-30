<?php
declare(strict_types=1);

namespace App\Error;

use Cake\Error\ErrorLogger;
use Cake\Error\PhpError;
use Psr\Http\Message\ServerRequestInterface;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\ExceptionMechanism;
use Throwable;
use function Sentry\captureEvent;
use function Sentry\captureMessage;

/**
 * Log errors and exceptions as Sentry events.
 */
class SentryErrorLogger extends ErrorLogger
{
    /**
     * @inheritDoc
     */
    public function logError(PhpError $error, ?ServerRequestInterface $request = null, bool $includeTrace = false): void
    {
        captureMessage($error->getMessage(), \Sentry\Severity::fromError($error->getCode()));

        parent::logError($error, $request, $includeTrace);
    }

    /**
     * @inheritDoc
     */
    public function logException(Throwable $exception, ?ServerRequestInterface $request = null, bool $includeTrace = false): void
    {
        $hint = EventHint::fromArray([
            'exception' => $exception,
            'mechanism' => new ExceptionMechanism(ExceptionMechanism::TYPE_GENERIC, false),
        ]);

        captureEvent(Event::createEvent(), $hint);

        parent::logException($exception, $request, $includeTrace);
    }
}
