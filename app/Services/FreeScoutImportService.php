<?php

namespace App\Services;

use App\Models\ImportProfile;
use App\Models\ImportMapping;
use Illuminate\Support\Facades\DB;
use Exception;

class FreeScoutImportService
{
    /**
     * Create default FreeScout import mappings for a profile.
     */
    public function createDefaultMappings(ImportProfile $profile): array
    {
        if ($profile->type !== 'freescout-postgres') {
            throw new Exception('This service only supports FreeScout PostgreSQL profiles');
        }

        $mappings = [];

        // 1. Users mapping (FreeScout users/admins → Service Vault users)
        $mappings[] = ImportMapping::create([
            'profile_id' => $profile->id,
            'source_table' => 'users',
            'destination_table' => 'users',
            'field_mappings' => [
                'id' => 'id', // Will be converted from integer to UUID
                'first_name' => 'name', // FreeScout splits name, we combine
                'last_name' => 'name', // Will need transformation rule
                'email' => 'email',
                'password' => 'password',
                'timezone' => 'timezone',
                'locale' => 'locale',
                'phone' => 'phone',
                'job_title' => 'job_title',
                'role' => 'user_type', // Transform role to user_type
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ],
            'transformation_rules' => [
                'id' => [
                    'type' => 'integer_to_uuid',
                    'source_field' => 'id',
                    'prefix' => 'freescout_user_',
                ],
                'name' => [
                    'type' => 'combine_fields',
                    'fields' => ['first_name', 'last_name'],
                    'separator' => ' ',
                ],
                'user_type' => [
                    'type' => 'role_mapping',
                    'mappings' => [
                        '1' => 'agent', // Admin
                        '2' => 'agent', // User
                    ],
                    'default' => 'employee',
                ],
            ],
            'where_conditions' => "role IN (1, 2)", // Only import admin/user roles
            'import_order' => 1,
            'is_active' => true,
        ]);

        // 2. Customers mapping (FreeScout customers → Service Vault accounts + users)
        // First create accounts for customers
        $mappings[] = ImportMapping::create([
            'profile_id' => $profile->id,
            'source_table' => 'customers',
            'destination_table' => 'accounts',
            'field_mappings' => [
                'id' => 'id', // Will be converted from integer to UUID
                'company' => 'name', // Use company name, fallback to customer name
                'first_name' => 'name', // Fallback if no company
                'last_name' => 'name', // Will be combined
                'phone' => 'phone',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ],
            'transformation_rules' => [
                'id' => [
                    'type' => 'integer_to_uuid',
                    'source_field' => 'id',
                    'prefix' => 'freescout_account_',
                ],
                'name' => [
                    'type' => 'conditional_name',
                    'primary_field' => 'company',
                    'fallback_fields' => ['first_name', 'last_name'],
                    'fallback_separator' => ' ',
                ],
                'account_type' => [
                    'type' => 'default_value',
                    'default' => 'customer',
                ],
            ],
            'import_order' => 2,
            'is_active' => true,
        ]);

        // Then create users for customers
        $mappings[] = ImportMapping::create([
            'profile_id' => $profile->id,
            'source_table' => 'customers',
            'destination_table' => 'users',
            'field_mappings' => [
                'id' => 'id', // Will be converted from integer to UUID (separate from account UUID)
                'first_name' => 'name',
                'last_name' => 'name', 
                'email' => 'email',
                'phone' => 'phone',
                'timezone' => 'timezone',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ],
            'transformation_rules' => [
                'id' => [
                    'type' => 'integer_to_uuid',
                    'source_field' => 'id',
                    'prefix' => 'freescout_customer_user_',
                ],
                'name' => [
                    'type' => 'combine_fields',
                    'fields' => ['first_name', 'last_name'],
                    'separator' => ' ',
                ],
                'user_type' => [
                    'type' => 'default_value',
                    'default' => 'account_user',
                ],
                'account_id' => [
                    'type' => 'lookup_by_source_uuid',
                    'lookup_table' => 'accounts',
                    'lookup_field' => 'id',
                    'source_field' => 'id',
                    'source_prefix' => 'freescout_account_',
                ],
            ],
            'where_conditions' => "email IS NOT NULL AND email != ''", // Only customers with email
            'import_order' => 3,
            'is_active' => true,
        ]);

        // 3. Conversations mapping (FreeScout conversations → Service Vault tickets)
        $mappings[] = ImportMapping::create([
            'profile_id' => $profile->id,
            'source_table' => 'conversations',
            'destination_table' => 'tickets',
            'field_mappings' => [
                'id' => 'id', // Will be converted from integer to UUID
                'number' => 'ticket_number',
                'subject' => 'title',
                'status' => 'status',
                'state' => 'status', // FreeScout uses both status and state
                'customer_id' => 'account_id', // Link to account (UUID lookup)
                'user_id' => 'assigned_user_id', // Assigned agent (UUID lookup)
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
                'closed_at' => 'closed_at',
            ],
            'transformation_rules' => [
                'id' => [
                    'type' => 'integer_to_uuid',
                    'source_field' => 'id',
                    'prefix' => 'freescout_conversation_',
                ],
                'status' => [
                    'type' => 'status_mapping',
                    'mappings' => [
                        '1' => 'open',      // Active
                        '2' => 'pending',   // Pending
                        '3' => 'closed',    // Closed
                        '4' => 'spam',      // Spam -> closed
                    ],
                    'default' => 'open',
                ],
                'account_id' => [
                    'type' => 'lookup_by_source_uuid',
                    'lookup_table' => 'accounts',
                    'lookup_field' => 'id',
                    'source_field' => 'customer_id',
                    'source_prefix' => 'freescout_account_',
                ],
                'assigned_user_id' => [
                    'type' => 'lookup_by_source_uuid',
                    'lookup_table' => 'users',
                    'lookup_field' => 'id',
                    'source_field' => 'user_id',
                    'source_prefix' => 'freescout_user_',
                    'nullable' => true,
                ],
                'priority' => [
                    'type' => 'default_value',
                    'default' => 'medium',
                ],
                'category' => [
                    'type' => 'default_value',
                    'default' => 'general',
                ],
            ],
            'import_order' => 4,
            'is_active' => true,
        ]);

        // 4. Threads mapping (FreeScout threads → Service Vault ticket_comments)
        $mappings[] = ImportMapping::create([
            'profile_id' => $profile->id,
            'source_table' => 'threads',
            'destination_table' => 'ticket_comments',
            'field_mappings' => [
                'id' => 'id', // Will be converted from integer to UUID
                'conversation_id' => 'ticket_id', // Link to ticket (UUID lookup)
                'user_id' => 'user_id', // User who commented (UUID lookup)
                'customer_id' => 'user_id', // If customer commented (UUID lookup)
                'body' => 'comment',
                'type' => 'is_internal', // Transform type to internal flag
                'source_via' => 'comment_type',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ],
            'transformation_rules' => [
                'id' => [
                    'type' => 'integer_to_uuid',
                    'source_field' => 'id',
                    'prefix' => 'freescout_thread_',
                ],
                'ticket_id' => [
                    'type' => 'lookup_by_source_uuid',
                    'lookup_table' => 'tickets',
                    'lookup_field' => 'id',
                    'source_field' => 'conversation_id',
                    'source_prefix' => 'freescout_conversation_',
                ],
                'user_id' => [
                    'type' => 'conditional_lookup_uuid',
                    'conditions' => [
                        [
                            'if_field' => 'user_id',
                            'if_not_null' => true,
                            'lookup_table' => 'users',
                            'lookup_field' => 'id',
                            'source_field' => 'user_id',
                            'source_prefix' => 'freescout_user_',
                        ],
                        [
                            'if_field' => 'customer_id', 
                            'if_not_null' => true,
                            'lookup_table' => 'users',
                            'lookup_field' => 'id',
                            'source_field' => 'customer_id',
                            'source_prefix' => 'freescout_customer_user_',
                        ],
                    ],
                ],
                'is_internal' => [
                    'type' => 'thread_type_mapping',
                    'mappings' => [
                        '1' => false, // MESSAGE (customer)
                        '2' => true,  // NOTE (internal)
                        '3' => false, // CUSTOMER (customer)
                    ],
                    'default' => false,
                ],
                'comment_type' => [
                    'type' => 'source_mapping',
                    'mappings' => [
                        '1' => 'email',
                        '2' => 'web',
                        '3' => 'api',
                    ],
                    'default' => 'web',
                ],
            ],
            'where_conditions' => "body IS NOT NULL AND body != ''", // Only threads with content
            'import_order' => 5,
            'is_active' => true,
        ]);

        return $mappings;
    }

    /**
     * Get available columns for each table in the database.
     */
    private function getTableColumns(string $connectionName): array
    {
        $tables = ['users', 'customers', 'conversations', 'threads'];
        $columns = [];
        
        foreach ($tables as $table) {
            try {
                $tableColumns = DB::connection($connectionName)
                    ->select("SELECT column_name FROM information_schema.columns WHERE table_name = ? AND table_schema = 'public'", [$table]);
                $columns[$table] = array_column($tableColumns, 'column_name');
            } catch (\Exception $e) {
                $columns[$table] = [];
            }
        }
        
        return $columns;
    }

    /**
     * Select only the available columns from the desired columns list.
     */
    private function selectAvailableColumns(array $availableColumns, array $desiredColumns): array
    {
        $selectedColumns = [];
        
        foreach ($desiredColumns as $column) {
            if (in_array($column, $availableColumns)) {
                $selectedColumns[] = $column;
            }
        }
        
        // If no desired columns are available, fall back to 'id' and any available columns
        if (empty($selectedColumns)) {
            if (in_array('id', $availableColumns)) {
                $selectedColumns[] = 'id';
            }
            
            // Add first few available columns as fallback
            $selectedColumns = array_merge($selectedColumns, array_slice($availableColumns, 0, 3));
        }
        
        return array_unique($selectedColumns);
    }

    /**
     * Get FreeScout schema recommendations for PostgreSQL.
     */
    public function getFreeScoutSchemaInfo(): array
    {
        return [
            'expected_tables' => [
                'users' => [
                    'description' => 'FreeScout administrators and agents',
                    'key_fields' => ['id', 'first_name', 'last_name', 'email', 'role'],
                    'expected_count' => '1-50 records (staff members)',
                ],
                'customers' => [
                    'description' => 'FreeScout customer contacts',
                    'key_fields' => ['id', 'first_name', 'last_name', 'email', 'company'],
                    'expected_count' => '100+ records (customers)',
                ],
                'conversations' => [
                    'description' => 'FreeScout support conversations (tickets)',
                    'key_fields' => ['id', 'number', 'subject', 'status', 'customer_id', 'user_id'],
                    'expected_count' => '500+ records (tickets)',
                ],
                'threads' => [
                    'description' => 'FreeScout conversation messages and notes',
                    'key_fields' => ['id', 'conversation_id', 'body', 'type', 'user_id', 'customer_id'],
                    'expected_count' => '1000+ records (messages)',
                ],
            ],
            'validation_queries' => [
                'users_count' => 'SELECT COUNT(*) FROM users WHERE role IN (1, 2)',
                'customers_with_email' => 'SELECT COUNT(*) FROM customers WHERE email IS NOT NULL',
                'active_conversations' => 'SELECT COUNT(*) FROM conversations WHERE status IN (1, 2, 3)',
                'threads_with_content' => 'SELECT COUNT(*) FROM threads WHERE body IS NOT NULL',
            ],
            'data_relationships' => [
                'conversations.customer_id → customers.id',
                'conversations.user_id → users.id (nullable)',
                'threads.conversation_id → conversations.id',
                'threads.user_id → users.id (nullable)',
                'threads.customer_id → customers.id (nullable)',
            ],
        ];
    }

    /**
     * Validate FreeScout database structure.
     */
    public function validateFreeScoutSchema(string $connectionName): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'recommendations' => [],
        ];

        try {
            // Check required tables exist
            $requiredTables = ['users', 'customers', 'conversations', 'threads'];
            $existingTables = DB::connection($connectionName)
                ->select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
            $existingTableNames = array_column($existingTables, 'table_name');

            foreach ($requiredTables as $table) {
                if (!in_array($table, $existingTableNames)) {
                    $validation['errors'][] = "Required table '{$table}' not found";
                    $validation['is_valid'] = false;
                }
            }

            if ($validation['is_valid']) {
                // Validate data counts
                $userCount = DB::connection($connectionName)->selectOne('SELECT COUNT(*) as count FROM users WHERE role IN (1, 2)')->count;
                $customerCount = DB::connection($connectionName)->selectOne('SELECT COUNT(*) as count FROM customers')->count;
                $conversationCount = DB::connection($connectionName)->selectOne('SELECT COUNT(*) as count FROM conversations')->count;

                if ($userCount == 0) {
                    $validation['warnings'][] = 'No admin/user records found in users table';
                }

                if ($customerCount == 0) {
                    $validation['errors'][] = 'No customer records found';
                    $validation['is_valid'] = false;
                }

                if ($conversationCount == 0) {
                    $validation['warnings'][] = 'No conversation records found';
                }

                // Check for email addresses
                $customersWithEmail = DB::connection($connectionName)
                    ->selectOne("SELECT COUNT(*) as count FROM customers WHERE email IS NOT NULL AND email != ''")->count;

                if ($customersWithEmail < $customerCount * 0.5) {
                    $validation['warnings'][] = 'Less than 50% of customers have email addresses';
                }

                // Provide recommendations
                $validation['recommendations'] = [
                    "Found {$userCount} staff members to import as Service Vault users",
                    "Found {$customerCount} customers to import as Service Vault accounts and users",
                    "Found {$conversationCount} conversations to import as Service Vault tickets",
                ];
            }

        } catch (Exception $e) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Database validation failed: ' . $e->getMessage();
        }

        return $validation;
    }

    /**
     * Get import preview with FreeScout-specific context.
     */
    public function getImportPreview(ImportProfile $profile, int $limit = 5): array
    {
        $connectionName = app(PostgreSQLConnectionService::class)->createConnection($profile);
        
        try {
            $preview = [];
            
            // Get available columns for each table to make queries flexible
            $availableColumns = $this->getTableColumns($connectionName);
            
            // Preview users - flexible column selection
            $userColumns = $this->selectAvailableColumns($availableColumns['users'] ?? [], 
                ['id', 'first_name', 'last_name', 'email', 'role']);
            $users = DB::connection($connectionName)
                ->select("SELECT " . implode(', ', $userColumns) . " FROM users WHERE role IN (1, 2) LIMIT ?", [$limit]);
            $preview['users'] = [
                'title' => 'FreeScout Staff → Service Vault Users',
                'description' => 'Admin and user accounts will become Service Vault agent users',
                'sample_data' => $users,
                'total_count' => DB::connection($connectionName)->selectOne('SELECT COUNT(*) as count FROM users WHERE role IN (1, 2)')->count ?? 0,
            ];

            // Preview customers - flexible column selection
            $customerColumns = $this->selectAvailableColumns($availableColumns['customers'] ?? [], 
                ['id', 'first_name', 'last_name', 'email', 'company']);
            $customers = DB::connection($connectionName)
                ->select("SELECT " . implode(', ', $customerColumns) . " FROM customers LIMIT ?", [$limit]);
            $preview['customers'] = [
                'title' => 'FreeScout Customers → Service Vault Accounts + Users',
                'description' => 'Each customer becomes an account with a user record',
                'sample_data' => $customers,
                'total_count' => DB::connection($connectionName)->selectOne('SELECT COUNT(*) as count FROM customers')->count ?? 0,
            ];

            // Preview conversations - flexible column selection with resolved relationships
            $conversationColumns = $this->selectAvailableColumns($availableColumns['conversations'] ?? [], 
                ['id', 'number', 'subject', 'status', 'customer_id', 'user_id', 'created_at']);
            
            // Build query with JOINs to resolve relationships
            $conversationQuery = "
                SELECT 
                    c." . implode(', c.', $conversationColumns) . ",
                    " . (in_array('customer_id', $conversationColumns) ? "
                        CASE 
                            WHEN cust.company IS NOT NULL AND cust.company != '' THEN cust.company
                            ELSE CONCAT(COALESCE(cust.first_name, ''), ' ', COALESCE(cust.last_name, ''))
                        END as customer_name," : "") . "
                    " . (in_array('user_id', $conversationColumns) ? "
                        CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user_name," : "") . "
                    " . (in_array('status', $conversationColumns) ? "
                        CASE 
                            WHEN c.status = 1 THEN 'Active (Open)'
                            WHEN c.status = 2 THEN 'Pending'
                            WHEN c.status = 3 THEN 'Closed'
                            WHEN c.status = 4 THEN 'Spam'
                            ELSE CONCAT('Status ', c.status)
                        END as status_name" : "") . "
                FROM conversations c
                " . (in_array('customer_id', $conversationColumns) ? "LEFT JOIN customers cust ON c.customer_id = cust.id" : "") . "
                " . (in_array('user_id', $conversationColumns) ? "LEFT JOIN users u ON c.user_id = u.id" : "") . "
                LIMIT ?
            ";
            
            $conversations = DB::connection($connectionName)->select($conversationQuery, [$limit]);
            $preview['conversations'] = [
                'title' => 'FreeScout Conversations → Service Vault Tickets',
                'description' => 'Support conversations become tickets with status mapping',
                'sample_data' => $conversations,
                'total_count' => DB::connection($connectionName)->selectOne('SELECT COUNT(*) as count FROM conversations')->count ?? 0,
            ];

            // Preview threads - flexible column selection with resolved relationships
            $threadColumns = $this->selectAvailableColumns($availableColumns['threads'] ?? [], 
                ['id', 'conversation_id', 'type', 'body', 'user_id', 'customer_id', 'created_at']);
            
            // Build query with JOINs to resolve relationships
            $threadQuery = "
                SELECT 
                    t." . implode(', t.', $threadColumns) . ",
                    c.subject as conversation_subject,
                    " . (in_array('user_id', $threadColumns) ? "
                        CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user_name," : "") . "
                    " . (in_array('customer_id', $threadColumns) ? "
                        CASE 
                            WHEN cust.company IS NOT NULL AND cust.company != '' THEN cust.company
                            ELSE CONCAT(COALESCE(cust.first_name, ''), ' ', COALESCE(cust.last_name, ''))
                        END as customer_name," : "") . "
                    " . (in_array('type', $threadColumns) ? "
                        CASE 
                            WHEN t.type = 1 THEN 'Message (Customer)'
                            WHEN t.type = 2 THEN 'Note (Internal)'
                            WHEN t.type = 3 THEN 'Reply (Agent)'
                            ELSE CONCAT('Type ', t.type)
                        END as type_name" : "") . "
                FROM threads t
                LEFT JOIN conversations c ON t.conversation_id = c.id
                " . (in_array('user_id', $threadColumns) ? "LEFT JOIN users u ON t.user_id = u.id" : "") . "
                " . (in_array('customer_id', $threadColumns) ? "LEFT JOIN customers cust ON t.customer_id = cust.id" : "") . "
                WHERE t.body IS NOT NULL 
                LIMIT ?
            ";
            
            $threads = DB::connection($connectionName)->select($threadQuery, [$limit]);
            $preview['threads'] = [
                'title' => 'FreeScout Threads → Service Vault Comments',
                'description' => 'Conversation messages and notes become ticket comments',
                'sample_data' => $threads,
                'total_count' => DB::connection($connectionName)->selectOne("SELECT COUNT(*) as count FROM threads WHERE body IS NOT NULL")->count,
            ];

            return $preview;
            
        } finally {
            app(PostgreSQLConnectionService::class)->closeConnection($connectionName);
        }
    }
}