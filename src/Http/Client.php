<?php
declare(strict_types=1);

namespace App\Http;

use Cake\Http\Client as CakeClient;
use Cake\Http\Client\Response;
use Psr\Http\Message\RequestInterface;
use Sentry\SentrySdk;
use Sentry\Tracing\Spans\Span;
use Throwable;

class Client extends CakeClient
{
    /**
     * @inheritDoc
     */
    protected function _sendRequest(RequestInterface $request, array $options): Response
    {
        $parentSpan = SentrySdk::getCurrentHub()->getSpan();
        $span = null;

        if ($parentSpan !== null) {
            $span = Span::make()
                ->setName(
                    sprintf(
                        '%s %s://%s%s',
                        strtoupper($request->getMethod()),
                        $request->getUri()->getScheme(),
                        $request->getUri()->getHost(),
                        $request->getUri()->getPath()
                    )
                )
                ->setAttribiute('sentry.op', 'http.client')
                ->setAttribiute('http.query', $request->getUri()->getQuery())
                ->setAttribiute('http.fragment', $request->getUri()->getFragment())
                ->start();
        }

        try {
            $response = parent::_sendRequest($request, $options);
        } catch (Throwable $e) {
            if ($span !== null) {
                $span->finish();
            }
            throw $e;
        }

        if ($span !== null) {
            $span
                ->setAttribiute('http.response.status_code', (string) $response->getStatusCode())
                ->finish();
        }

        return $response;
    }
}
