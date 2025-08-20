<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Populate rate_at_time field for existing time entries from their linked billing rates
        DB::statement("
            UPDATE time_entries 
            SET rate_at_time = br.rate
            FROM billing_rates br
            WHERE time_entries.billing_rate_id = br.id 
            AND time_entries.rate_at_time IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set rate_at_time back to null for entries that were populated by this migration
        DB::statement("
            UPDATE time_entries 
            SET rate_at_time = NULL
            WHERE billing_rate_id IS NOT NULL
        ");
    }
};