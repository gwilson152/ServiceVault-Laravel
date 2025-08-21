<?php

namespace App\Services;

use App\Models\ImportTemplate;
use App\Models\ImportProfile;
use App\Models\ImportQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Exception;

class TemplateService
{
    protected PostgreSQLConnectionService $connectionService;

    public function __construct(PostgreSQLConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Get all available templates.
     */
    public function getAvailableTemplates(): Collection
    {
        return ImportTemplate::active()
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get templates for a specific platform.
     */
    public function getTemplatesForPlatform(string $platform): Collection
    {
        return ImportTemplate::active()
            ->forPlatform($platform)
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Apply a template to an import profile.
     */
    public function applyTemplate(ImportProfile $profile, ImportTemplate $template): void
    {
        // Set the template relationship
        $profile->update(['template_id' => $template->id]);

        // Create queries from template configuration
        $this->createQueriesFromTemplate($profile, $template->configuration);
    }

    /**
     * Create import queries from template configuration.
     */
    public function createQueriesFromTemplate(ImportProfile $profile, array $templateConfig): array
    {
        $createdQueries = [];
        $queries = $templateConfig['queries'] ?? [];

        foreach ($queries as $index => $queryConfig) {
            $query = ImportQuery::create([
                'profile_id' => $profile->id,
                'name' => $queryConfig['name'] ?? "Query " . ($index + 1),
                'description' => $queryConfig['description'] ?? null,
                'base_table' => $queryConfig['base_table'],
                'joins' => $queryConfig['joins'] ?? null,
                'where_conditions' => $queryConfig['where_conditions'] ?? null,
                'select_fields' => $queryConfig['select_fields'] ?? null,
                'order_by' => $queryConfig['order_by'] ?? null,
                'limit_clause' => $queryConfig['limit_clause'] ?? null,
                'destination_table' => $queryConfig['destination_table'],
                'field_mappings' => $queryConfig['field_mappings'] ?? [],
                'transformation_rules' => $queryConfig['transformation_rules'] ?? null,
                'validation_rules' => $queryConfig['validation_rules'] ?? null,
                'import_order' => $queryConfig['import_order'] ?? $index,
                'is_active' => $queryConfig['is_active'] ?? true,
            ]);

            $createdQueries[] = $query;
        }

        return $createdQueries;
    }

    /**
     * Attempt to detect the best template for a database.
     */
    public function detectBestTemplate(string $connectionName): ?ImportTemplate
    {
        try {
            $tables = $this->connectionService->getTables($connectionName);
            $tableNames = array_map(fn($table) => $table->table_name ?? $table['table_name'], $tables);

            // Check for FreeScout indicators
            $freeScoutTables = ['customers', 'conversations', 'threads', 'users', 'mailboxes'];
            $freeScoutMatches = array_intersect($tableNames, $freeScoutTables);

            if (count($freeScoutMatches) >= 3) {
                return ImportTemplate::system()
                    ->forPlatform('freescout')
                    ->first();
            }

            // Check for other platform indicators
            // Add more detection logic here as needed

            // Default to custom template
            return ImportTemplate::system()
                ->forPlatform('custom')
                ->first();

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Create a new custom template.
     */
    public function createTemplate(array $data): ImportTemplate
    {
        return ImportTemplate::create([
            'name' => $data['name'],
            'platform' => $data['platform'] ?? 'custom',
            'description' => $data['description'] ?? null,
            'database_type' => $data['database_type'] ?? 'postgresql',
            'configuration' => $data['configuration'] ?? ['queries' => []],
            'is_system' => false,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Update an existing template.
     */
    public function updateTemplate(ImportTemplate $template, array $data): ImportTemplate
    {
        // Only allow updating non-system templates or by admin users
        if ($template->is_system && !Auth::user()?->isAdmin()) {
            throw new Exception('Cannot modify system templates');
        }

        $template->update([
            'name' => $data['name'] ?? $template->name,
            'platform' => $data['platform'] ?? $template->platform,
            'description' => $data['description'] ?? $template->description,
            'database_type' => $data['database_type'] ?? $template->database_type,
            'configuration' => $data['configuration'] ?? $template->configuration,
            'is_active' => $data['is_active'] ?? $template->is_active,
        ]);

        return $template;
    }

    /**
     * Export a template configuration.
     */
    public function exportTemplate(ImportTemplate $template): array
    {
        return [
            'name' => $template->name,
            'platform' => $template->platform,
            'description' => $template->description,
            'database_type' => $template->database_type,
            'configuration' => $template->configuration,
            'version' => '1.0',
            'exported_at' => now()->toISOString(),
        ];
    }

    /**
     * Import a template configuration.
     */
    public function importTemplate(array $templateData): ImportTemplate
    {
        return $this->createTemplate([
            'name' => $templateData['name'] . ' (Imported)',
            'platform' => $templateData['platform'] ?? 'custom',
            'description' => $templateData['description'] ?? null,
            'database_type' => $templateData['database_type'] ?? 'postgresql',
            'configuration' => $templateData['configuration'] ?? ['queries' => []],
        ]);
    }

    /**
     * Create system templates (called during installation/setup).
     */
    public function createSystemTemplates(): void
    {
        // FreeScout Template
        $this->createFreeScoutTemplate();

        // Custom Template
        $this->createCustomTemplate();
    }

    /**
     * Create the FreeScout system template.
     */
    protected function createFreeScoutTemplate(): ImportTemplate
    {
        $configuration = [
            'metadata' => [
                'platform' => 'freescout',
                'version' => '1.0',
                'description' => 'Standard FreeScout database import template',
            ],
            'settings' => [
                'default_user_type' => 'account_user',
                'default_account_creation' => true,
            ],
            'queries' => [
                [
                    'name' => 'Customer Users with Emails',
                    'description' => 'Import FreeScout customers as Service Vault account users',
                    'base_table' => 'customers',
                    'joins' => [
                        [
                            'table' => 'customer_emails',
                            'type' => 'LEFT JOIN',
                            'on' => 'customers.id = customer_emails.customer_id',
                            'alias' => 'emails'
                        ]
                    ],
                    'select_fields' => [
                        'customers.id',
                        'customers.first_name',
                        'customers.last_name',
                        'customers.photo_url',
                        'customers.created_at',
                        'customers.updated_at',
                        'emails.email'
                    ],
                    'where_conditions' => 'emails.email IS NOT NULL',
                    'destination_table' => 'users',
                    'field_mappings' => [
                        'customers.id' => 'id',
                        'customers.first_name' => 'name',
                        'customers.last_name' => 'name',
                        'emails.email' => 'email',
                        'customers.photo_url' => 'avatar',
                        'customers.created_at' => 'created_at',
                        'customers.updated_at' => 'updated_at'
                    ],
                    'transformation_rules' => [
                        'id' => [
                            'type' => 'uuid_convert',
                            'prefix' => 'freescout_customer_'
                        ],
                        'name' => [
                            'type' => 'combine',
                            'fields' => ['customers.first_name', 'customers.last_name'],
                            'separator' => ' '
                        ],
                        'user_type' => [
                            'type' => 'static',
                            'value' => 'account_user'
                        ]
                    ],
                    'validation_rules' => [
                        'email' => [
                            ['type' => 'required'],
                            ['type' => 'email']
                        ]
                    ],
                    'import_order' => 0,
                    'is_active' => true
                ],
                [
                    'name' => 'Service Tickets',
                    'description' => 'Import FreeScout conversations as Service Vault tickets',
                    'base_table' => 'conversations',
                    'joins' => [
                        [
                            'table' => 'customers',
                            'type' => 'LEFT JOIN',
                            'on' => 'conversations.customer_id = customers.id',
                            'alias' => 'customer'
                        ],
                        [
                            'table' => 'mailboxes',
                            'type' => 'LEFT JOIN',
                            'on' => 'conversations.mailbox_id = mailboxes.id',
                            'alias' => 'mailbox'
                        ]
                    ],
                    'select_fields' => [
                        'conversations.id',
                        'conversations.subject',
                        'conversations.status',
                        'conversations.priority',
                        'conversations.customer_id',
                        'conversations.user_id',
                        'conversations.created_at',
                        'conversations.updated_at',
                        'customer.first_name',
                        'customer.last_name',
                        'mailbox.name as mailbox_name'
                    ],
                    'destination_table' => 'tickets',
                    'field_mappings' => [
                        'conversations.id' => 'id',
                        'conversations.subject' => 'title',
                        'conversations.status' => 'status',
                        'conversations.priority' => 'priority',
                        'conversations.customer_id' => 'customer_id',
                        'conversations.user_id' => 'agent_id',
                        'conversations.created_at' => 'created_at',
                        'conversations.updated_at' => 'updated_at'
                    ],
                    'transformation_rules' => [
                        'id' => [
                            'type' => 'uuid_convert',
                            'prefix' => 'freescout_ticket_'
                        ],
                        'customer_id' => [
                            'type' => 'uuid_convert',
                            'prefix' => 'freescout_customer_'
                        ],
                        'agent_id' => [
                            'type' => 'uuid_convert',
                            'prefix' => 'freescout_user_'
                        ],
                        'description' => [
                            'type' => 'static',
                            'value' => 'Imported from FreeScout'
                        ]
                    ],
                    'import_order' => 1,
                    'is_active' => true
                ],
                [
                    'name' => 'Staff Users',
                    'description' => 'Import FreeScout staff as Service Vault agent users',
                    'base_table' => 'users',
                    'where_conditions' => 'role IN (1, 2)', // Admin and User roles
                    'destination_table' => 'users',
                    'field_mappings' => [
                        'id' => 'id',
                        'first_name' => 'name',
                        'last_name' => 'name',
                        'email' => 'email',
                        'password' => 'password',
                        'timezone' => 'timezone',
                        'locale' => 'locale',
                        'phone' => 'phone',
                        'job_title' => 'job_title',
                        'created_at' => 'created_at',
                        'updated_at' => 'updated_at'
                    ],
                    'transformation_rules' => [
                        'id' => [
                            'type' => 'uuid_convert',
                            'prefix' => 'freescout_user_'
                        ],
                        'name' => [
                            'type' => 'combine',
                            'fields' => ['first_name', 'last_name'],
                            'separator' => ' '
                        ],
                        'user_type' => [
                            'type' => 'static',
                            'value' => 'agent'
                        ]
                    ],
                    'import_order' => 2,
                    'is_active' => true
                ],
                [
                    'name' => 'Time Entries',
                    'description' => 'Import FreeScout time tracking data as Service Vault time entries (if available)',
                    'base_table' => 'time_logs', // Common time tracking table name
                    'joins' => [
                        [
                            'table' => 'conversations',
                            'type' => 'LEFT JOIN',
                            'on' => 'time_logs.conversation_id = conversations.id',
                            'alias' => 'ticket'
                        ],
                        [
                            'table' => 'users',
                            'type' => 'LEFT JOIN',
                            'on' => 'time_logs.user_id = users.id',
                            'alias' => 'agent'
                        ],
                        [
                            'table' => 'customers',
                            'type' => 'LEFT JOIN',
                            'on' => 'conversations.customer_id = customers.id',
                            'alias' => 'customer'
                        ]
                    ],
                    'select_fields' => [
                        'time_logs.id',
                        'time_logs.user_id',
                        'time_logs.conversation_id',
                        'time_logs.description',
                        'time_logs.start_time',
                        'time_logs.end_time',
                        'time_logs.duration',
                        'time_logs.billable',
                        'time_logs.rate',
                        'time_logs.created_at',
                        'time_logs.updated_at',
                        'conversations.subject as ticket_title',
                        'customers.id as customer_id'
                    ],
                    'where_conditions' => 'time_logs.duration > 0 AND time_logs.user_id IS NOT NULL',
                    'destination_table' => 'time_entries',
                    'field_mappings' => [
                        'time_logs.id' => 'id',
                        'time_logs.user_id' => 'user_id',
                        'time_logs.conversation_id' => 'ticket_id',
                        'time_logs.description' => 'description',
                        'time_logs.start_time' => 'started_at',
                        'time_logs.end_time' => 'ended_at',
                        'time_logs.duration' => 'duration',
                        'time_logs.billable' => 'billable',
                        'time_logs.rate' => 'rate_at_time',
                        'time_logs.created_at' => 'created_at',
                        'time_logs.updated_at' => 'updated_at'
                    ],
                    'transformation_rules' => [
                        'id' => [
                            'type' => 'uuid_convert',
                            'prefix' => 'freescout_time_'
                        ],
                        'user_id' => [
                            'type' => 'uuid_convert',
                            'prefix' => 'freescout_user_'
                        ],
                        'ticket_id' => [
                            'type' => 'uuid_convert',
                            'prefix' => 'freescout_ticket_'
                        ],
                        'duration' => [
                            'type' => 'time_to_minutes'
                        ],
                        'account_id' => [
                            'type' => 'account_from_ticket'
                        ],
                        'billing_rate_id' => [
                            'type' => 'billing_rate_lookup'
                        ],
                        'status' => [
                            'type' => 'static',
                            'value' => 'approved'
                        ],
                        'billable' => [
                            'type' => 'static',
                            'value' => true
                        ]
                    ],
                    'validation_rules' => [
                        'user_id' => [
                            ['type' => 'required'],
                            ['type' => 'user_exists']
                        ],
                        'duration' => [
                            ['type' => 'required'],
                            ['type' => 'duration_range', 'min_minutes' => 1, 'max_minutes' => 1440]
                        ],
                        'started_at' => [
                            ['type' => 'required']
                        ],
                        'ticket_id' => [
                            ['type' => 'ticket_exists']
                        ],
                        'time_range' => [
                            ['type' => 'time_range_valid', 'start_field' => 'started_at', 'end_field' => 'ended_at', 'max_hours' => 24]
                        ],
                        'no_duplicates' => [
                            ['type' => 'no_duplicate_time']
                        ]
                    ],
                    'import_order' => 3,
                    'is_active' => false // Disabled by default - user can enable if time tracking exists
                ]
            ]
        ];

        return ImportTemplate::updateOrCreate(
            ['platform' => 'freescout', 'is_system' => true],
            [
                'name' => 'FreeScout Standard',
                'platform' => 'freescout',
                'description' => 'Standard template for importing FreeScout databases',
                'database_type' => 'postgresql',
                'configuration' => $configuration,
                'is_system' => true,
                'is_active' => true,
                'created_by' => null,
            ]
        );
    }

    /**
     * Create the custom system template.
     */
    protected function createCustomTemplate(): ImportTemplate
    {
        $configuration = [
            'metadata' => [
                'platform' => 'custom',
                'version' => '1.0',
                'description' => 'Blank template for custom database imports',
            ],
            'settings' => [
                'instructions' => 'Use the query builder to create custom import queries for your database',
            ],
            'queries' => [] // Empty - user will build their own
        ];

        return ImportTemplate::updateOrCreate(
            ['platform' => 'custom', 'is_system' => true],
            [
                'name' => 'Custom Database',
                'platform' => 'custom',
                'description' => 'Blank template for custom database imports',
                'database_type' => 'postgresql',
                'configuration' => $configuration,
                'is_system' => true,
                'is_active' => true,
                'created_by' => null,
            ]
        );
    }
}