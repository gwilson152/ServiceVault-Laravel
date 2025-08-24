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
