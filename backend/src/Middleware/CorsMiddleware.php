<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Http\CorsBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Cors middleware
 */
class CorsMiddleware implements MiddlewareInterface
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
        $response = $handler->handle($request);

        return $response->cors($request)
            ->allowOrigin([
                env('APP_FRONTEND_URL'),
            ])
            ->allowHeaders([
                'content-type',
                'baggage',
                'sentry-trace',
            ])
            ->allowCredentials()
            ->build();
    }
}
