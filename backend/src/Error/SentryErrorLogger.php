<?php
declare(strict_types=1);

namespace App\Error;

use Cake\Error\ErrorLogger;
use Cake\Error\PhpError;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

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
        \Sentry\captureMessage($error->getMessage(), \Sentry\Severity::fromError($error->getCode()));

        parent::logError($error, $request, $includeTrace);
    }

    /**
     * @inheritDoc
     */
    public function logException(
        Throwable $exception,
        ?ServerRequestInterface $request = null,
        bool $includeTrace = false
    ): void {
        \Sentry\captureException($exception);

        parent::logException($exception, $request, $includeTrace);
    }
}
