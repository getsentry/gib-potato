<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sentry\Metrics\MetricsUnit;
use Sentry\SentrySdk;
use Sentry\State\Scope;
use function Sentry\metrics;

/**
 * SentryUser middleware
 */
class SentryUserMiddleware implements MiddlewareInterface
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
        $user = $request->getAttribute('identity');

        if ($user) {
            SentrySdk::getCurrentHub()->configureScope(function (Scope $scope) use ($user): void {
                $scope->setUser([
                    'id' => $user->id,
                    'username' => $user->slack_name,
                ]);

                metrics()->set(
                    key: 'gibpotato.users.web_ui',
                    value: $user->id,
                    unit: MetricsUnit::custom('user_id'),
                );

                $span = $scope->getSpan();
                if ($span !== null) {
                    $span->setData([
                        'gibpotato.users.web_ui' => $user->id,
                    ]);
                }
            });
        }

        return $handler->handle($request);
    }
}
