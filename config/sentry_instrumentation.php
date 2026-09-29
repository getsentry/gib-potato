<?php
declare(strict_types=1);

namespace App\Sentry;

use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Database\StatementInterface;
use Cake\Http\Client as CakeClient;
use Cake\Http\Client\Response;
use Psr\Http\Message\RequestInterface;
use function Sentry\instrument;

const CAKEPHP_DB_ORIGIN = 'auto.db.cakephp';
const CAKEPHP_HTTP_ORIGIN = 'auto.http.cakephp';
const DB_SYSTEM = 'postgresql';
const HTTP_CLIENT_OP = 'http.client';
const QUERY_OP = 'db.sql.query';
const TRANSACTION_OP = 'db.sql.transaction';

if (function_exists('\Sentry\instrument')) {
    instrument(
        'executeStatement',
        class: Driver::class,
        op: QUERY_OP,
        origin: CAKEPHP_DB_ORIGIN,
        attributes: [
            'db.system' => DB_SYSTEM,
        ],
        preprocessing: static function (StatementInterface $statement, ?array $params = null): array {
            $sql = $statement->queryString();
            $query = trim(preg_replace('/\s+/', ' ', $sql) ?? $sql);
            // Handle parenthesized statements like "(SELECT ...)" and capture the SQL operation.
            $operation = preg_match('/^\(*(\w+)/', $query, $matches) === 1
                ? strtoupper($matches[1])
                : 'QUERY';

            return [
                'description' => $query,
                'db.operation' => $operation,
                'db.query.text' => $query,
                'db.query.parameter_count' => count($params ?? $statement->getBoundParams()),
            ];
        },
    );

    instrument(
        'exec',
        class: Driver::class,
        op: QUERY_OP,
        origin: CAKEPHP_DB_ORIGIN,
        attributes: [
            'db.system' => DB_SYSTEM,
        ],
        preprocessing: static function (string $sql): array {
            $query = trim(preg_replace('/\s+/', ' ', $sql) ?? $sql);
            // Handle parenthesized statements like "(SELECT ...)" and capture the SQL operation.
            $operation = preg_match('/^\(*(\w+)/', $query, $matches) === 1
                ? strtoupper($matches[1])
                : 'QUERY';

            return [
                'description' => $query,
                'db.operation' => $operation,
                'db.query.text' => $query,
                'db.query.parameter_count' => 0,
            ];
        },
    );

    instrument(
        'transactional',
        class: Connection::class,
        description: 'TRANSACTION',
        op: TRANSACTION_OP,
        origin: CAKEPHP_DB_ORIGIN,
        attributes: [
            'db.system' => DB_SYSTEM,
            'db.operation' => 'TRANSACTION',
        ],
    );

    instrument(
        '_sendRequest',
        class: CakeClient::class,
        op: HTTP_CLIENT_OP,
        origin: CAKEPHP_HTTP_ORIGIN,
        preprocessing: static function (RequestInterface $request): array {
            $uri = $request->getUri();

            return [
                'description' => sprintf(
                    '%s %s://%s%s%s',
                    strtoupper($request->getMethod()),
                    $uri->getScheme(),
                    $uri->getHost(),
                    $uri->getPort() !== null ? ':' . $uri->getPort() : '',
                    $uri->getPath(),
                ),
                'http.request.method' => $request->getMethod(),
                'http.query' => $uri->getQuery(),
                'http.fragment' => $uri->getFragment(),
                'server.address' => $uri->getHost(),
                'server.port' => $uri->getPort(),
            ];
        },
        postprocessing: static function (Response $response): array {
            return [
                'http.response.status_code' => $response->getStatusCode(),
            ];
        },
    );
}
