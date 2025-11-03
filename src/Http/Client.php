<?php
declare(strict_types=1);

namespace App\Http;

use Cake\Http\Client as CakeClient;
use Cake\Http\Client\Response;
use Psr\Http\Message\RequestInterface;
use Sentry\SentrySdk;
use function Sentry\startSpan;
use function Sentry\getTraceparent;
use function Sentry\getBaggage;

class Client extends CakeClient
{
    /**
     * @inheritDoc
     */
    protected function _sendRequest(RequestInterface $request, array $options): Response
    {
        $span = startSpan(sprintf(
            '%s %s://%s%s',
            strtoupper($request->getMethod()),
            $request->getUri()->getScheme(),
            $request->getUri()->getHost(),
            $request->getUri()->getPath(),
        ));
        $span->setAttributes([
            'sentry.op' => 'http.client',
            'http.query' => $request->getUri()->getQuery(),
            'http.fragment' => $request->getUri()->getFragment(),
        ]);

        $traceparent = getTraceparent();
        if ($traceparent !== '') {
            $request = $request->withHeader('sentry-trace', $traceparent);
        }

        $baggage = getBaggage();
        if ($baggage !== '') {
            $request = $request->withHeader('baggage', $baggage);
        }

        $response = parent::_sendRequest($request, $options);

        $span->setAttribute('http.response_code', (string)$response->getStatusCode());
        $span->finish();

        return $response;
    }
}
