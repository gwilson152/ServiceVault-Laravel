<?php

namespace App\Services;

use App\Models\ImportProfile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use Exception;

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
        $connectionName = 'import_' . $profile->id;
        
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
            $errorMsg = "Failed to create connection: " . $e->getMessage();
            $errorMsg .= "\nConnection config: " . json_encode($config);
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
            throw new Exception("Failed to retrieve schema info: " . $e->getMessage());
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
            $testTableName = 'test_write_access_' . uniqid();
            
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
                'error' => 'Could not determine user permissions: ' . $e->getMessage(),
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
            throw new Exception("Failed to retrieve server info: " . $e->getMessage());
        }
    }

    /**
     * Introspect FreeScout database to find email-related tables and relationships.
     */
    public function introspectFreeScoutEmails(string $connectionName): array
    {
        try {
            $emailTables = [];
            
            // Find all tables that might contain email data
            $allTables = DB::connection($connectionName)->select("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_type = 'BASE TABLE'
                ORDER BY table_name
            ");
            
            foreach ($allTables as $table) {
                $tableName = $table->table_name;
                
                // Get columns for each table to find email-related fields
                $columns = DB::connection($connectionName)->select("
                    SELECT column_name, data_type, is_nullable, column_default
                    FROM information_schema.columns 
                    WHERE table_name = ? AND table_schema = 'public'
                    ORDER BY ordinal_position
                ", [$tableName]);
                
                // Check if table has email-related columns
                $emailColumns = [];
                $customerRelated = false;
                $hasId = false;
                
                foreach ($columns as $column) {
                    $columnName = strtolower($column->column_name);
                    
                    if (strpos($columnName, 'email') !== false) {
                        $emailColumns[] = [
                            'name' => $column->column_name,
                            'type' => $column->data_type,
                            'nullable' => $column->is_nullable
                        ];
                    }
                    
                    if (strpos($columnName, 'customer') !== false) {
                        $customerRelated = true;
                    }
                    
                    if ($columnName === 'id') {
                        $hasId = true;
                    }
                }
                
                if (!empty($emailColumns)) {
                    // Get sample data to understand the structure
                    $sampleData = [];
                    try {
                        $sampleData = DB::connection($connectionName)
                            ->select("SELECT * FROM {$tableName} LIMIT 3");
                    } catch (\Exception $e) {
                        // Some tables might have permission issues
                    }
                    
                    // Get row count
                    $rowCount = 0;
                    try {
                        $countResult = DB::connection($connectionName)
                            ->selectOne("SELECT COUNT(*) as count FROM {$tableName}");
                        $rowCount = $countResult->count ?? 0;
                    } catch (\Exception $e) {
                        // Ignore count errors
                    }
                    
                    $emailTables[$tableName] = [
                        'email_columns' => $emailColumns,
                        'all_columns' => $columns,
                        'customer_related' => $customerRelated,
                        'has_id' => $hasId,
                        'row_count' => $rowCount,
                        'sample_data' => $sampleData
                    ];
                }
            }
            
            // Look for foreign key relationships that might connect customers to emails
            $foreignKeys = DB::connection($connectionName)->select("
                SELECT
                    tc.table_name,
                    kcu.column_name,
                    ccu.table_name AS foreign_table_name,
                    ccu.column_name AS foreign_column_name,
                    tc.constraint_name
                FROM information_schema.table_constraints AS tc
                JOIN information_schema.key_column_usage AS kcu
                    ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema = kcu.table_schema
                JOIN information_schema.constraint_column_usage AS ccu
                    ON ccu.constraint_name = tc.constraint_name
                    AND ccu.table_schema = tc.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY'
                AND tc.table_schema = 'public'
                AND (tc.table_name LIKE '%email%' 
                     OR tc.table_name LIKE '%customer%'
                     OR ccu.table_name LIKE '%email%'
                     OR ccu.table_name LIKE '%customer%')
            ");
            
            return [
                'email_tables' => $emailTables,
                'foreign_keys' => $foreignKeys,
                'analysis' => $this->analyzeEmailStructure($emailTables, $foreignKeys)
            ];
            
        } catch (Exception $e) {
            return [
                'error' => 'Failed to introspect emails: ' . $e->getMessage(),
                'email_tables' => [],
                'foreign_keys' => []
            ];
        }
    }
    
    /**
     * Introspect FreeScout database for time tracking tables and relationships.
     */
    public function introspectFreeScoutTimeTracking(string $connectionName): array
    {
        try {
            $timeTables = [];
            
            // Find all tables that might contain time tracking data
            $allTables = DB::connection($connectionName)->select("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_type = 'BASE TABLE'
                ORDER BY table_name
            ");
            
            foreach ($allTables as $table) {
                $tableName = $table->table_name;
                
                // Check if table name suggests time tracking
                $isTimeRelated = $this->isTimeTrackingTable($tableName);
                
                if ($isTimeRelated) {
                    // Get columns for time-related tables
                    $columns = DB::connection($connectionName)->select("
                        SELECT column_name, data_type, is_nullable, column_default
                        FROM information_schema.columns 
                        WHERE table_name = ? AND table_schema = 'public'
                        ORDER BY ordinal_position
                    ", [$tableName]);
                    
                    // Analyze columns for time tracking indicators
                    $timeColumns = [];
                    $durationColumns = [];
                    $userRelated = false;
                    $conversationRelated = false;
                    
                    foreach ($columns as $column) {
                        $columnName = strtolower($column->column_name);
                        
                        if (preg_match('/(start|begin|from).*time|time.*(start|begin|from)/i', $columnName)) {
                            $timeColumns['start'] = $column->column_name;
                        }
                        
                        if (preg_match('/(end|stop|to).*time|time.*(end|stop|to)/i', $columnName)) {
                            $timeColumns['end'] = $column->column_name;
                        }
                        
                        if (preg_match('/duration|elapsed|spent|hours|minutes|seconds/i', $columnName)) {
                            $durationColumns[] = [
                                'name' => $column->column_name,
                                'type' => $column->data_type
                            ];
                        }
                        
                        if (preg_match('/user_id|agent_id|staff_id/i', $columnName)) {
                            $userRelated = true;
                        }
                        
                        if (preg_match('/conversation|ticket|thread/i', $columnName)) {
                            $conversationRelated = true;
                        }
                    }
                    
                    // Get sample data
                    $sampleData = [];
                    $rowCount = 0;
                    
                    try {
                        $sampleData = DB::connection($connectionName)
                            ->select("SELECT * FROM {$tableName} LIMIT 3");
                        
                        $countResult = DB::connection($connectionName)
                            ->selectOne("SELECT COUNT(*) as count FROM {$tableName}");
                        $rowCount = $countResult->count ?? 0;
                        
                    } catch (\Exception $e) {
                        // Some tables might have permission issues
                    }
                    
                    $timeTables[$tableName] = [
                        'table_name' => $tableName,
                        'all_columns' => $columns,
                        'time_columns' => $timeColumns,
                        'duration_columns' => $durationColumns,
                        'user_related' => $userRelated,
                        'conversation_related' => $conversationRelated,
                        'row_count' => $rowCount,
                        'sample_data' => $sampleData,
                        'confidence' => $this->calculateTimeTableConfidence($timeColumns, $durationColumns, $userRelated, $conversationRelated)
                    ];
                }
            }
            
            // Get foreign key relationships for time tables
            $timeTableNames = array_keys($timeTables);
            $foreignKeys = [];
            
            if (!empty($timeTableNames)) {
                $foreignKeys = DB::connection($connectionName)->select("
                    SELECT
                        tc.table_name,
                        kcu.column_name,
                        ccu.table_name AS foreign_table_name,
                        ccu.column_name AS foreign_column_name,
                        tc.constraint_name
                    FROM information_schema.table_constraints AS tc
                    JOIN information_schema.key_column_usage AS kcu
                        ON tc.constraint_name = kcu.constraint_name
                        AND tc.table_schema = kcu.table_schema
                    JOIN information_schema.constraint_column_usage AS ccu
                        ON ccu.constraint_name = tc.constraint_name
                        AND ccu.table_schema = tc.table_schema
                    WHERE tc.constraint_type = 'FOREIGN KEY'
                    AND tc.table_schema = 'public'
                    AND (tc.table_name = ANY(?) OR ccu.table_name = ANY(?))
                ", ['{' . implode(',', $timeTableNames) . '}', '{' . implode(',', $timeTableNames) . '}']);
            }
            
            return [
                'time_tables' => $timeTables,
                'foreign_keys' => $foreignKeys,
                'analysis' => $this->analyzeTimeTrackingStructure($timeTables, $foreignKeys)
            ];
            
        } catch (Exception $e) {
            return [
                'error' => 'Failed to introspect time tracking: ' . $e->getMessage(),
                'time_tables' => [],
                'foreign_keys' => []
            ];
        }
    }

    /**
     * Check if a table name suggests time tracking functionality.
     */
    private function isTimeTrackingTable(string $tableName): bool
    {
        $timeIndicators = [
            'time', 'timer', 'track', 'log', 'hour', 'work',
            'duration', 'effort', 'activity', 'session'
        ];
        
        $lowerTableName = strtolower($tableName);
        
        foreach ($timeIndicators as $indicator) {
            if (strpos($lowerTableName, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Calculate confidence score for time tracking table.
     */
    private function calculateTimeTableConfidence(array $timeColumns, array $durationColumns, bool $userRelated, bool $conversationRelated): string
    {
        $score = 0;
        
        // Time columns add confidence
        if (isset($timeColumns['start'])) $score += 2;
        if (isset($timeColumns['end'])) $score += 2;
        if (!empty($durationColumns)) $score += 2;
        
        // Relationships add confidence
        if ($userRelated) $score += 1;
        if ($conversationRelated) $score += 1;
        
        if ($score >= 5) return 'high';
        if ($score >= 3) return 'medium';
        return 'low';
    }

    /**
     * Analyze time tracking structure and provide recommendations.
     */
    private function analyzeTimeTrackingStructure(array $timeTables, array $foreignKeys): array
    {
        $analysis = [
            'recommendations' => [],
            'likely_time_table' => null,
            'suggested_mappings' => []
        ];
        
        // Find the most likely time tracking table
        $bestTable = null;
        $bestScore = 0;
        
        foreach ($timeTables as $tableName => $tableInfo) {
            $confidence = $tableInfo['confidence'];
            $score = $confidence === 'high' ? 3 : ($confidence === 'medium' ? 2 : 1);
            
            if ($tableInfo['row_count'] > 0) {
                $score += 1; // Bonus for having data
            }
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTable = $tableName;
            }
            
            $analysis['recommendations'][] = "Table '{$tableName}' has {$confidence} confidence for time tracking ({$tableInfo['row_count']} rows)";
        }
        
        if ($bestTable) {
            $analysis['likely_time_table'] = $bestTable;
            $tableInfo = $timeTables[$bestTable];
            
            // Generate suggested field mappings
            $mappings = [];
            
            if (isset($tableInfo['time_columns']['start'])) {
                $mappings[$tableInfo['time_columns']['start']] = 'started_at';
            }
            
            if (isset($tableInfo['time_columns']['end'])) {
                $mappings[$tableInfo['time_columns']['end']] = 'ended_at';
            }
            
            foreach ($tableInfo['duration_columns'] as $durationCol) {
                $mappings[$durationCol['name']] = 'duration';
                break; // Use first duration column
            }
            
            // Look for common field patterns
            foreach ($tableInfo['all_columns'] as $column) {
                $colName = strtolower($column->column_name);
                
                if (preg_match('/^(id|time_id|log_id)$/', $colName)) {
                    $mappings[$column->column_name] = 'id';
                }
                
                if (preg_match('/user_id|agent_id|staff_id/', $colName)) {
                    $mappings[$column->column_name] = 'user_id';
                }
                
                if (preg_match('/conversation_id|ticket_id|thread_id/', $colName)) {
                    $mappings[$column->column_name] = 'ticket_id';
                }
                
                if (preg_match('/description|comment|note|summary/', $colName)) {
                    $mappings[$column->column_name] = 'description';
                }
                
                if (preg_match('/billable|chargeable/', $colName)) {
                    $mappings[$column->column_name] = 'billable';
                }
                
                if (preg_match('/rate|amount|cost|price/', $colName)) {
                    $mappings[$column->column_name] = 'rate_at_time';
                }
            }
            
            $analysis['suggested_mappings'] = $mappings;
        }
        
        // Analyze foreign key relationships
        foreach ($foreignKeys as $fk) {
            if ($fk->foreign_table_name === 'users' || $fk->foreign_table_name === 'conversations') {
                $analysis['recommendations'][] = "Foreign key: {$fk->table_name}.{$fk->column_name} → {$fk->foreign_table_name}.{$fk->foreign_column_name}";
            }
        }
        
        return $analysis;
    }

    /**
     * Analyze the email structure and provide recommendations.
     */
    private function analyzeEmailStructure(array $emailTables, array $foreignKeys): array
    {
        $analysis = [
            'recommendations' => [],
            'likely_email_table' => null,
            'customer_email_join' => null
        ];
        
        // Look for tables that are likely to contain customer emails
        foreach ($emailTables as $tableName => $tableInfo) {
            if ($tableInfo['customer_related'] && !empty($tableInfo['email_columns'])) {
                $analysis['recommendations'][] = "Table '{$tableName}' appears to be customer-email related with " . count($tableInfo['email_columns']) . " email columns";
                
                if ($tableInfo['row_count'] > 0) {
                    $analysis['likely_email_table'] = $tableName;
                }
            } elseif (!empty($tableInfo['email_columns']) && $tableInfo['row_count'] > 0) {
                $analysis['recommendations'][] = "Table '{$tableName}' contains email data ({$tableInfo['row_count']} rows)";
            }
        }
        
        // Analyze foreign key relationships
        foreach ($foreignKeys as $fk) {
            if (isset($emailTables[$fk->table_name]) || isset($emailTables[$fk->foreign_table_name])) {
                $analysis['recommendations'][] = "Foreign key relationship: {$fk->table_name}.{$fk->column_name} → {$fk->foreign_table_name}.{$fk->foreign_column_name}";
                
                if ($fk->foreign_table_name === 'customers' || $fk->table_name === 'customers') {
                    $analysis['customer_email_join'] = [
                        'email_table' => $fk->table_name !== 'customers' ? $fk->table_name : $fk->foreign_table_name,
                        'join_column' => $fk->column_name,
                        'customer_column' => $fk->foreign_column_name
                    ];
                }
            }
        }
        
        return $analysis;
    }
}