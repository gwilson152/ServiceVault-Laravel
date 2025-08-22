<?php

namespace Database\Seeders;

use App\Models\ImportTemplate;
use Illuminate\Database\Seeder;

class ImportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // FreeScout Template
        ImportTemplate::create([
            'name' => 'FreeScout Platform',
            'platform' => 'freescout',
            'description' => 'Import configuration optimized for FreeScout help desk platform with support for customers, conversations, threads, and time tracking.',
            'database_type' => 'postgresql',
            'configuration' => [
                'queries' => [
                    'customers' => [
                        'name' => 'Customer Account Users',
                        'description' => 'Import customer users with email resolution',
                        'base_table' => 'customers',
                        'joins' => [
                            [
                                'type' => 'LEFT',
                                'table' => 'emails',
                                'on' => 'emails.customer_id = customers.id',
                                'condition' => "emails.type = 'work'",
                            ],
                        ],
                        'fields' => [
                            'customers.id as external_id',
                            'customers.first_name',
                            'customers.last_name',
                            'emails.email',
                            'customers.company',
                            'customers.job_title',
                            'customers.phone',
                            'customers.timezone',
                            'customers.country',
                            'customers.state',
                            'customers.city',
                            'customers.zip',
                            'customers.address',
                            'customers.created_at',
                            'customers.updated_at',
                        ],
                        'target_type' => 'customer_users',
                        'transformations' => [
                            'name' => "CONCAT(first_name, ' ', last_name)",
                            'user_type' => "'customer'",
                            'is_active' => 'true',
                        ],
                    ],
                    'conversations' => [
                        'name' => 'Support Tickets',
                        'description' => 'Import conversations as service tickets',
                        'base_table' => 'conversations',
                        'joins' => [
                            [
                                'type' => 'LEFT',
                                'table' => 'customers',
                                'on' => 'customers.id = conversations.customer_id',
                            ],
                            [
                                'type' => 'LEFT',
                                'table' => 'emails',
                                'on' => 'emails.customer_id = customers.id',
                                'condition' => "emails.type = 'work'",
                            ],
                            [
                                'type' => 'LEFT',
                                'table' => 'users',
                                'on' => 'users.id = conversations.user_id',
                            ],
                        ],
                        'fields' => [
                            'conversations.id as external_id',
                            'conversations.number as ticket_number',
                            'conversations.subject',
                            'conversations.body as description',
                            'conversations.status',
                            'conversations.state',
                            'conversations.user_id as assigned_user_external_id',
                            'customers.id as customer_external_id',
                            'emails.email as customer_email',
                            'conversations.mailbox_id',
                            'conversations.folder_id',
                            'conversations.created_at',
                            'conversations.updated_at',
                        ],
                        'target_type' => 'tickets',
                        'transformations' => [
                            'title' => 'subject',
                            'priority' => "'medium'",
                            'category' => "'imported'",
                            'status' => "CASE 
                                WHEN status = 1 THEN 'open'
                                WHEN status = 2 THEN 'pending'
                                WHEN status = 3 THEN 'closed'
                                ELSE 'open'
                            END",
                        ],
                    ],
                    'time_entries' => [
                        'name' => 'Time Tracking Entries',
                        'description' => 'Import time tracking data from custom time_logs table',
                        'base_table' => 'time_logs',
                        'joins' => [
                            [
                                'type' => 'LEFT',
                                'table' => 'conversations',
                                'on' => 'conversations.id = time_logs.conversation_id',
                            ],
                            [
                                'type' => 'LEFT',
                                'table' => 'users',
                                'on' => 'users.id = time_logs.user_id',
                            ],
                        ],
                        'fields' => [
                            'time_logs.id as external_id',
                            'time_logs.description',
                            'time_logs.time_spent as duration_seconds',
                            'time_logs.date as started_at',
                            'time_logs.user_id as user_external_id',
                            'time_logs.conversation_id as ticket_external_id',
                            'time_logs.billable',
                            'time_logs.rate_amount',
                            'time_logs.created_at',
                            'time_logs.updated_at',
                        ],
                        'target_type' => 'time_entries',
                        'transformations' => [
                            'duration' => 'ROUND(duration_seconds / 60)', // Convert seconds to minutes
                            'billable' => 'COALESCE(billable, true)',
                            'rate_override' => 'rate_amount',
                        ],
                    ],
                ],
                'priority_order' => ['customers', 'conversations', 'time_entries'],
                'suggested_filters' => [
                    'customers' => [
                        'active_only' => "customers.created_at >= '2020-01-01'",
                        'with_email' => 'emails.email IS NOT NULL',
                    ],
                    'conversations' => [
                        'recent_only' => "conversations.created_at >= DATE('now', '-1 year')",
                        'exclude_spam' => "conversations.state != 'spam'",
                    ],
                    'time_entries' => [
                        'billable_only' => 'time_logs.billable = true',
                        'recent_entries' => "time_logs.date >= DATE('now', '-6 months')",
                    ],
                ],
            ],
            'is_system' => true,
            'is_active' => true,
            'created_by' => null,
        ]);

        // Custom/Generic Template
        ImportTemplate::create([
            'name' => 'Custom Database',
            'platform' => 'custom',
            'description' => 'Flexible template for importing from any PostgreSQL database. Configure your own queries and field mappings.',
            'database_type' => 'postgresql',
            'configuration' => [
                'queries' => [],
                'priority_order' => [],
                'suggested_filters' => [],
            ],
            'is_system' => true,
            'is_active' => true,
            'created_by' => null,
        ]);
    }
}
