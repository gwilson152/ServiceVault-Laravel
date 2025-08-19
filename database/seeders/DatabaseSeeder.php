<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Note: User creation is handled by the setup page (/setup)
     * This seeder only contains system reference data.
     */
    public function run(): void
    {
        // Seed system reference data
        $this->call([
            RoleTemplateSeeder::class,
            BillingRateSeeder::class,  // Default billing rates
            AddonTemplateSeeder::class,
            TicketStatusSeeder::class,
            TicketCategorySeeder::class,
            TicketPrioritySeeder::class,
            TicketSeeder::class,  // Sample tickets for demonstration
        ]);
    }
}
