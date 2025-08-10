<?php

namespace Database\Seeders;

use App\Models\AddonTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddonTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Hardware Items
            [
                'name' => 'Network Cable (Cat6)',
                'description' => 'Category 6 Ethernet network cable',
                'category' => 'hardware',
                'unit_type' => 'each',
                'default_price' => 15.99,
                'default_quantity' => 1,
                'is_taxable' => true,
                'requires_approval' => false,
                'is_active' => true
            ],
            [
                'name' => 'Network Switch (24-Port)',
                'description' => '24-port managed network switch',
                'category' => 'hardware',
                'unit_type' => 'each',
                'default_price' => 299.99,
                'default_quantity' => 1,
                'is_taxable' => true,
                'requires_approval' => true,
                'is_active' => true
            ],
            
            // Software Licenses
            [
                'name' => 'Microsoft Office 365 Business',
                'description' => 'Monthly subscription per user',
                'category' => 'license',
                'unit_type' => 'months',
                'default_price' => 12.50,
                'default_quantity' => 1,
                'is_taxable' => false,
                'requires_approval' => false,
                'is_active' => true
            ],
            
            // Services
            [
                'name' => 'On-Site Technical Support',
                'description' => 'On-site technical support service',
                'category' => 'service',
                'unit_type' => 'hours',
                'default_price' => 150.00,
                'default_quantity' => 1,
                'is_taxable' => false,
                'requires_approval' => false,
                'is_active' => true
            ],
            [
                'name' => 'Remote Support Session',
                'description' => 'Remote technical support session',
                'category' => 'service',
                'unit_type' => 'hours',
                'default_price' => 75.00,
                'default_quantity' => 1,
                'is_taxable' => false,
                'requires_approval' => false,
                'is_active' => true
            ],
            [
                'name' => 'System Backup Service',
                'description' => 'Automated backup service setup and configuration',
                'category' => 'service',
                'unit_type' => 'each',
                'default_price' => 200.00,
                'default_quantity' => 1,
                'is_taxable' => false,
                'requires_approval' => true,
                'is_active' => true
            ]
        ];

        foreach ($templates as $template) {
            AddonTemplate::firstOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}