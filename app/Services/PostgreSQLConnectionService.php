<?php

namespace App\Services;

use App\Models\ImportProfile;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;

class PostgreSQLConnectionService
{
    /**
     * Test PostgreSQL connection with given configuration.
     */
    public function testConnection(array $config): array
    {
        // Only handle PostgreSQL connections for now
        if (isset($config['database_type']) && $config['database_type'] !== 'postgresql') {
            return [
                'success' => false,
                'message' => 'Unsupported database type',
                'error' => "Database type '{$config['database_type']}' is not supported yet. Only PostgreSQL is currently supported.",
            ];
        }

        try {
            $dsn = $this->buildDsn($config);
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10, // 10 second connection timeout
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Test the connection with a simple query
            $stmt = $pdo->query('SELECT version() as version, current_database() as database, current_user as user');
            $result = $stmt->fetch();

            return [
                'success' => true,
                'message' => 'Connection successful',
                'server_info' => [
                    'version' => $result['version'],
                    'database' => $result['database'],
                    'user' => $result['user'],
                    'connection_time' => now()->toISOString(),
                ],
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Connection failed',
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Unexpected error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a dynamic PostgreSQL connection for an import profile.
     */
    public function createConnection(ImportProfile $profile): string
    {
        $connectionName = 'import_'.$profile->id;

        // Build Laravel-compatible configuration
        $config = [
            'driver' => 'pgsql',
            'url' => null,
            'host' => $profile->host,
            'port' => $profile->port,
            'database' => $profile->database,
            'username' => $profile->username,
            'password' => $profile->password, // This will be decrypted automatically
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => $profile->ssl_mode,
            'options' => [],
        ];

        // Add connection to Laravel's database configuration
        Config::set("database.connections.{$connectionName}", $config);

        // Test the connection
        try {
            DB::connection($connectionName)->getPdo();

            return $connectionName;
        } catch (Exception $e) {
            // Add more detailed error information
            $errorMsg = 'Failed to create connection: '.$e->getMessage();
            $errorMsg .= "\nConnection config: ".json_encode($config);
            throw new Exception($errorMsg);
        }
    }

    /**
     * Get PostgreSQL database schema information.
     */
    public function getSchemaInfo(string $connectionName): array
    {
        try {
            $tables = $this->getTables($connectionName);
            $schemaInfo = [];

            foreach ($tables as $table) {
                $schemaInfo[$table->table_name] = [
                    'table_name' => $table->table_name,
                    'table_comment' => $table->table_comment,
                    'columns' => $this->getTableColumns($connectionName, $table->table_name),
                    'foreign_keys' => $this->getTableForeignKeys($connectionName, $table->table_name),
                ];
            }

            return $schemaInfo;
        } catch (Exception $e) {
            throw new Exception('Failed to retrieve schema info: '.$e->getMessage());
        }
    }

    /**
     * Get all tables from PostgreSQL database.
     */
    public function getTables(string $connectionName): array
    {
        $query = "
            SELECT 
                t.table_name,
                obj_description(c.oid) as table_comment
            FROM information_schema.tables t
            LEFT JOIN pg_class c ON c.relname = t.table_name
            LEFT JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE t.table_schema = 'public'
            AND t.table_type = 'BASE TABLE'
            AND n.nspname = 'public'
            ORDER BY t.table_name
        ";

        return DB::connection($connectionName)->select($query);
    }

    /**
     * Get columns for a specific table.
     */
    public function getTableColumns(string $connectionName, string $tableName): array
    {
        $query = "
            SELECT 
                c.column_name,
                c.data_type,
                c.is_nullable,
                c.column_default,
                c.character_maximum_length,
                c.numeric_precision,
                c.numeric_scale,
                col_description(pgc.oid, c.ordinal_position) as column_comment
            FROM information_schema.columns c
            LEFT JOIN pg_class pgc ON pgc.relname = c.table_name
            LEFT JOIN pg_namespace pgn ON pgn.oid = pgc.relnamespace
            WHERE c.table_name = ?
            AND c.table_schema = 'public'
            AND pgn.nspname = 'public'
            ORDER BY c.ordinal_position
        ";

        return DB::connection($connectionName)->select($query, [$tableName]);
    }

    /**
     * Get foreign key relationships for a table.
     */
    public function getTableForeignKeys(string $connectionName, string $tableName): array
    {
        $query = "
            SELECT
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name,
                rc.constraint_name
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
            JOIN information_schema.referential_constraints AS rc
                ON tc.constraint_name = rc.constraint_name
            WHERE tc.constraint_type = 'FOREIGN KEY'
            AND tc.table_name = ?
            AND tc.table_schema = 'public'
        ";

        return DB::connection($connectionName)->select($query, [$tableName]);
    }

    /**
     * Execute a query and return results as a generator for memory efficiency.
     */
    public function executeQuery(string $connectionName, string $query, array $bindings = [], int $chunkSize = 1000): \Generator
    {
        $pdo = DB::connection($connectionName)->getPdo();
        $stmt = $pdo->prepare($query);
        $stmt->execute($bindings);

        while ($batch = $stmt->fetchAll(PDO::FETCH_ASSOC)) {
            if (empty($batch)) {
                break;
            }

            foreach ($batch as $row) {
                yield $row;
            }

            // For large datasets, we might want to implement chunking here
            if (count($batch) < $chunkSize) {
                break;
            }
        }
    }

    /**
     * Get row count for a table with optional conditions.
     */
    public function getRowCount(string $connectionName, string $tableName, string $whereClause = ''): int
    {
        $query = "SELECT COUNT(*) as count FROM {$tableName}";

        if ($whereClause) {
            $query .= " WHERE {$whereClause}";
        }

        $result = DB::connection($connectionName)->selectOne($query);

        return $result->count;
    }

    /**
     * Validate that user has read-only access to the database.
     */
    public function validateReadOnlyAccess(string $connectionName): array
    {
        try {
            // Try to create a temporary table (should fail for read-only users)
            $testTableName = 'test_write_access_'.uniqid();

            try {
                DB::connection($connectionName)->statement("CREATE TEMP TABLE {$testTableName} (id INT)");
                DB::connection($connectionName)->statement("DROP TABLE {$testTableName}");

                return [
                    'is_read_only' => false,
                    'warning' => 'User has write access to the database. For security, consider using a read-only user.',
                ];
            } catch (Exception $e) {
                return [
                    'is_read_only' => true,
                    'message' => 'User has appropriate read-only access.',
                ];
            }
        } catch (Exception $e) {
            return [
                'is_read_only' => null,
                'error' => 'Could not determine user permissions: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Close a dynamic connection.
     */
    public function closeConnection(string $connectionName): void
    {
        DB::disconnect($connectionName);
    }

    /**
     * Build PostgreSQL DSN from configuration array.
     */
    private function buildDsn(array $config): string
    {
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";

        if (isset($config['sslmode'])) {
            $dsn .= ";sslmode={$config['sslmode']}";
        }

        return $dsn;
    }

    /**
     * Preview data from a table (limited rows for UI preview).
     */
    public function previewTableData(string $connectionName, string $tableName, int $limit = 10): array
    {
        $query = "SELECT * FROM {$tableName} LIMIT ?";

        return DB::connection($connectionName)->select($query, [$limit]);
    }

    /**
     * Get PostgreSQL server information.
     */
    public function getServerInfo(string $connectionName): array
    {
        try {
            $versionResult = DB::connection($connectionName)->selectOne('SELECT version() as version');
            $dbResult = DB::connection($connectionName)->selectOne('SELECT current_database() as database, current_user as user');

            return [
                'version' => $versionResult->version,
                'database' => $dbResult->database,
                'user' => $dbResult->user,
                'connection_status' => 'connected',
                'tested_at' => now()->toISOString(),
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to retrieve server info: '.$e->getMessage());
        }
    }

    /**
     * Get estimated record count for a query or table.
     */
    public function getEstimatedRecordCount(string $connectionName, string $baseTable, array $joins = [], array $filters = []): int
    {
        try {
            // Start with a simple count query
            $query = "SELECT COUNT(*) as count FROM {$baseTable}";
            $params = [];

            // Add JOIN clauses if provided
            if (! empty($joins)) {
                foreach ($joins as $join) {
                    $joinType = $join['type'] ?? 'INNER';
                    $query .= " {$joinType} JOIN {$join['table']} ON {$join['condition']}";
                }
            }

            // Add WHERE clauses if provided
            if (! empty($filters)) {
                $whereClauses = [];
                foreach ($filters as $filter) {
                    $whereClauses[] = $filter['condition'];
                    if (isset($filter['value'])) {
                        $params[] = $filter['value'];
                    }
                }
                if (! empty($whereClauses)) {
                    $query .= ' WHERE '.implode(' AND ', $whereClauses);
                }
            }

            $result = DB::connection($connectionName)->selectOne($query, $params);

            return (int) $result->count;

        } catch (Exception $e) {
            // If exact count fails, try to get table statistics estimate
            try {
                $result = DB::connection($connectionName)->selectOne(
                    'SELECT reltuples::bigint AS estimate FROM pg_class WHERE relname = ?',
                    [$baseTable]
                );

                return $result ? (int) $result->estimate : 0;
            } catch (Exception $e2) {
                return 0;
            }
        }
    }

    /**
     * Execute a query and return sample results.
     */
    public function executeQuerySample(string $connectionName, string $sql, array $params = [], int $limit = 10): array
    {
        try {
            // Add LIMIT to the query if not already present
            if (stripos($sql, 'LIMIT') === false) {
                $sql .= " LIMIT {$limit}";
            }

            return DB::connection($connectionName)->select($sql, $params);
        } catch (Exception $e) {
            throw new Exception('Query execution failed: '.$e->getMessage());
        }
    }

    /**
     * Validate and analyze a SQL query.
     */
    public function validateQuery(string $connectionName, string $sql): array
    {
        try {
            // Try to prepare the query with EXPLAIN to validate syntax
            $explainQuery = 'EXPLAIN '.$sql;
            $result = DB::connection($connectionName)->select($explainQuery);

            // Count the number of JOINs
            $joinCount = preg_match_all('/\bJOIN\b/i', $sql);

            // Estimate complexity
            $complexity = 'low';
            if ($joinCount >= 3) {
                $complexity = 'high';
            } elseif ($joinCount >= 1) {
                $complexity = 'medium';
            }

            return [
                'is_valid' => true,
                'execution_plan' => $result,
                'join_count' => $joinCount,
                'complexity' => $complexity,
                'warnings' => [],
            ];

        } catch (Exception $e) {
            return [
                'is_valid' => false,
                'error' => $e->getMessage(),
                'execution_plan' => null,
                'join_count' => 0,
                'complexity' => 'unknown',
                'warnings' => [],
            ];
        }
    }
}
