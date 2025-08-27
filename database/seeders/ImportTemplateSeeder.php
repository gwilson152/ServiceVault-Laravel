<?php

namespace Database\Seeders;

use App\Models\ImportTemplate;
use Illuminate\Database\Seeder;

class ImportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Custom/Generic Template
        ImportTemplate::create([
            'name' => 'Custom Database',
            'description' => 'Flexible template for importing from any PostgreSQL database. Configure your own queries and field mappings.',
            'source_type' => 'database',
            'default_configuration' => [
                'queries' => [],
                'priority_order' => [],
                'suggested_filters' => [],
            ],
            'field_mappings' => [],
            'is_active' => true,
        ]);

        // FreeScout Template
        ImportTemplate::create([
            'name' => 'FreeScout',
            'description' => 'Import users, accounts, tickets, and conversations from FreeScout help desk system.',
            'source_type' => 'database',
            'default_configuration' => [
                'queries' => [
                    'users' => 'SELECT id, email, first_name, last_name, role, created_at, updated_at FROM users WHERE deleted_at IS NULL',
                    'customers' => 'SELECT id, email, first_name, last_name, created_at, updated_at FROM customers',
                    'conversations' => 'SELECT id, number, subject, status, state, customer_id, user_id, created_at, updated_at FROM conversations',
                    'threads' => 'SELECT id, conversation_id, user_id, customer_id, type, state, body, created_at FROM threads',
                ],
                'priority_order' => ['users', 'customers', 'conversations', 'threads'],
                'suggested_filters' => [
                    'date_range' => 'created_at >= NOW() - INTERVAL \'1 year\'',
                    'active_only' => 'deleted_at IS NULL',
                ],
            ],
            'field_mappings' => [
                'users' => [
                    'external_id' => 'id',
                    'email' => 'email',
                    'name' => "CONCAT(first_name, ' ', last_name)",
                    'user_type' => 'agent',
                ],
                'customers' => [
                    'external_id' => 'id', 
                    'email' => 'email',
                    'name' => "CONCAT(first_name, ' ', last_name)",
                    'user_type' => 'account_user',
                ],
                'conversations' => [
                    'external_id' => 'id',
                    'ticket_number' => 'number',
                    'title' => 'subject',
                    'status' => 'status',
                ],
                'threads' => [
                    'external_id' => 'id',
                    'content' => 'body',
                    'type' => 'comment',
                ],
            ],
            'is_active' => true,
        ]);

        if ($this->command) {
            $this->command->info('Created import templates');
        }
    }
}