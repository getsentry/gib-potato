<?php
declare(strict_types=1);

namespace App\Http;

use Cake\Http\Client as CakeClient;
use Cake\Http\Client\Response;
use Psr\Http\Message\RequestInterface;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanContext;

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
            $context = new SpanContext();
            $context->setOp('http.client');
            $context->setDescription(
                sprintf(
                    '%s %s://%s%s',
                    strtoupper($request->getMethod()),
                    $request->getUri()->getScheme(),
                    $request->getUri()->getHost(),
                    $request->getUri()->getPath()
                )
            );
            $context->setData([
                'http.query' => $request->getUri()->getQuery(),
                'http.fragment' => $request->getUri()->getFragment(),
            ]);
            $span = $parentSpan->startChild($context);
        }

        try {
            $response = parent::_sendRequest($request, $options);
        } finally {
            if ($span !== null) {
                $span->setHttpStatus($response->getStatusCode());
                $span->finish();
            }
        }

        return $response;
    }
}
