<?php
declare(strict_types=1);

namespace App\Sentry;

use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Database\StatementInterface;
use function Sentry\instrument;

const CAKEPHP_DB_ORIGIN = 'auto.db.cakephp';
const DB_SYSTEM = 'postgresql';
const QUERY_OP = 'db.sql.query';
const TRANSACTION_OP = 'db.sql.transaction';

if (function_exists('Sentry\instrument')) {
    instrument(
        Driver::class,
        'executeStatement',
        preprocessing: static function (StatementInterface $statement, ?array $params = null): array {
            return sqlMetadata($statement->queryString(), $params ?? $statement->getBoundParams());
        },
    );

    instrument(
        Driver::class,
        'exec',
        preprocessing: static fn(string $sql): array => sqlMetadata($sql),
    );

    instrument(
        Connection::class,
        'transactional',
        ...spanMetadata(
            description: 'TRANSACTION',
            op: TRANSACTION_OP,
            operation: 'TRANSACTION',
        ),
    );
}

/**
 * @param array $params Bound parameters.
 * @return array<string, mixed>
 */
function sqlMetadata(string $sql, array $params = []): array
{
    $query = trim(preg_replace('/\s+/', ' ', $sql) ?? $sql);
    // Handle parenthesized statements like "(SELECT ...)" and capture the SQL operation.
    $operation = preg_match('/^\(*(\w+)/', $query, $matches) === 1
        ? strtoupper($matches[1])
        : 'QUERY';

    return spanMetadata(
        description: $query,
        op: QUERY_OP,
        operation: $operation,
        attributes: [
            'db.query.text' => $query,
            'db.query.parameter_count' => count($params),
        ],
    );
}

/**
 * @param array<string, mixed> $attributes
 * @return array<string, mixed>
 */
function spanMetadata(string $description, string $op, string $operation, array $attributes = []): array
{
    return [
        'description' => $description,
        'op' => $op,
        'origin' => CAKEPHP_DB_ORIGIN,
        'db.system' => DB_SYSTEM,
        'db.operation' => $operation,
        ...$attributes,
    ];
}
