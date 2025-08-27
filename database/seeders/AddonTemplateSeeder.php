<?php

namespace Database\Seeders;

use App\Models\AddonTemplate;
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
                'default_amount' => 15.99,
                'is_active' => true,
            ],
            [
                'name' => 'Network Switch (24-Port)',
                'description' => '24-port managed network switch',
                'default_amount' => 299.99,
                'is_active' => true,
            ],

            // Software Licenses
            [
                'name' => 'Microsoft Office 365 Business',
                'description' => 'Monthly subscription per user',
                'default_amount' => 12.50,
                'is_active' => true,
            ],

            // Services
            [
                'name' => 'On-Site Technical Support',
                'description' => 'On-site technical support service',
                'default_amount' => 150.00,
                'is_active' => true,
            ],
            [
                'name' => 'Remote Support Session',
                'description' => 'Remote technical support session',
                'default_amount' => 75.00,
                'is_active' => true,
            ],
            [
                'name' => 'System Backup Service',
                'description' => 'Automated backup service setup and configuration',
                'default_amount' => 200.00,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            AddonTemplate::firstOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
