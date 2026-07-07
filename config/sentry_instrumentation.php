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

if (function_exists(function: 'Sentry\instrument')) {
    instrument(
        class_name: Driver::class,
        function_name: 'executeStatement',
        op: QUERY_OP,
        origin: CAKEPHP_DB_ORIGIN,
        attributes: [
            'db.system' => DB_SYSTEM,
        ],
        preprocessing: static function (StatementInterface $statement, ?array $params = null): array {
            $sql = $statement->queryString();
            $query = trim(string: preg_replace(pattern: '/\s+/', replacement: ' ', subject: $sql) ?? $sql);
            // Handle parenthesized statements like "(SELECT ...)" and capture the SQL operation.
            $operation = preg_match(pattern: '/^\(*(\w+)/', subject: $query, matches: $matches) === 1
                ? strtoupper(string: $matches[1])
                : 'QUERY';

            return [
                'description' => $query,
                'db.operation' => $operation,
                'db.query.text' => $query,
                'db.query.parameter_count' => count(value: $params ?? $statement->getBoundParams()),
            ];
        },
    );

    instrument(
        class_name: Driver::class,
        function_name: 'exec',
        op: QUERY_OP,
        origin: CAKEPHP_DB_ORIGIN,
        attributes: [
            'db.system' => DB_SYSTEM,
        ],
        preprocessing: static function (string $sql): array {
            $query = trim(string: preg_replace(pattern: '/\s+/', replacement: ' ', subject: $sql) ?? $sql);
            // Handle parenthesized statements like "(SELECT ...)" and capture the SQL operation.
            $operation = preg_match(pattern: '/^\(*(\w+)/', subject: $query, matches: $matches) === 1
                ? strtoupper(string: $matches[1])
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
        class_name: Connection::class,
        function_name: 'transactional',
        description: 'TRANSACTION',
        op: TRANSACTION_OP,
        origin: CAKEPHP_DB_ORIGIN,
        attributes: [
            'db.system' => DB_SYSTEM,
            'db.operation' => 'TRANSACTION',
        ],
    );
}
