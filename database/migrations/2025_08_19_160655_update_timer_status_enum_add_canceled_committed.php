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
        // First, update any existing 'stopped' timers to 'canceled' as default
        // This handles existing data before changing the enum
        DB::table('timers')
            ->where('status', 'stopped')
            ->update(['status' => 'canceled']);

        // PostgreSQL-specific approach: Drop and recreate the check constraint
        if (DB::getDriverName() === 'pgsql') {
            // Drop the existing check constraint
            DB::statement('ALTER TABLE timers DROP CONSTRAINT IF EXISTS timers_status_check');
            
            // Change the column type to VARCHAR to remove enum constraint
            DB::statement('ALTER TABLE timers ALTER COLUMN status TYPE VARCHAR(20)');
            
            // Add new check constraint with updated values
            DB::statement("ALTER TABLE timers ADD CONSTRAINT timers_status_check CHECK (status IN ('running', 'paused', 'canceled', 'committed'))");
            
            // Set default value
            DB::statement("ALTER TABLE timers ALTER COLUMN status SET DEFAULT 'running'");
        } else {
            // Use Laravel schema builder for other databases (MySQL, etc.)
            Schema::table('timers', function (Blueprint $table) {
                $table->enum('status', ['running', 'paused', 'canceled', 'committed'])
                      ->default('running')
                      ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert 'canceled' and 'committed' back to 'stopped' for rollback
        DB::table('timers')
            ->whereIn('status', ['canceled', 'committed'])
            ->update(['status' => 'stopped']);

        // PostgreSQL-specific approach: Drop and recreate the check constraint
        if (DB::getDriverName() === 'pgsql') {
            // Drop the current check constraint
            DB::statement('ALTER TABLE timers DROP CONSTRAINT IF EXISTS timers_status_check');
            
            // Add original check constraint
            DB::statement("ALTER TABLE timers ADD CONSTRAINT timers_status_check CHECK (status IN ('running', 'paused', 'stopped'))");
            
            // Set default value
            DB::statement("ALTER TABLE timers ALTER COLUMN status SET DEFAULT 'running'");
        } else {
            // Use Laravel schema builder for other databases (MySQL, etc.)
            Schema::table('timers', function (Blueprint $table) {
                $table->enum('status', ['running', 'paused', 'stopped'])
                      ->default('running')
                      ->change();
            });
        }
    }
};
