<?php

namespace Database\Seeders;

use App\Models\BillingRate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BillingRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $billingRates = [
            [
                'name' => 'Standard Hourly',
                'description' => 'Standard hourly rate for general technical work',
                'rate' => 90.00,
                'is_active' => true,
                'is_default' => true, // This will be the default rate
            ],
            [
                'name' => 'Critical Hourly',
                'description' => 'Emergency and critical support rate',
                'rate' => 130.00,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Development',
                'description' => 'Software development and programming work',
                'rate' => 65.00,
                'is_active' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Travel',
                'description' => 'On-site travel and transportation time',
                'rate' => 40.00,
                'is_active' => true,
                'is_default' => false,
            ],
        ];

        foreach ($billingRates as $rateData) {
            BillingRate::firstOrCreate(
                ['name' => $rateData['name']],
                $rateData
            );
        }

        $this->command->info('Billing rates seeded successfully.');
    }
}
