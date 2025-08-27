<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('import_profiles', function (Blueprint $table) {
            // Sync scheduling configuration
            $table->boolean('sync_enabled')->default(false)->after('import_stats')
                ->comment('Enable/disable scheduled syncs for this profile');
                
            $table->enum('sync_frequency', ['hourly', 'daily', 'weekly', 'monthly', 'custom'])
                ->default('daily')->after('sync_enabled')
                ->comment('Frequency of scheduled syncs');
                
            $table->string('sync_cron_expression')->nullable()->after('sync_frequency')
                ->comment('Custom cron expression for advanced scheduling');
                
            $table->time('sync_time')->default('02:00')->after('sync_cron_expression')
                ->comment('Preferred time for daily/weekly syncs');
                
            $table->string('sync_timezone')->default('UTC')->after('sync_time')
                ->comment('Timezone for scheduled execution');

            // Sync tracking and statistics
            $table->timestamp('last_sync_at')->nullable()->after('sync_timezone')
                ->comment('Timestamp of last successful sync');
                
            $table->timestamp('next_sync_at')->nullable()->after('last_sync_at')
                ->comment('Calculated timestamp for next scheduled sync');
                
            $table->json('sync_options')->nullable()->after('next_sync_at')
                ->comment('Additional sync configuration options');
                
            $table->json('sync_stats')->nullable()->after('sync_options')
                ->comment('Historical sync performance statistics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'sync_enabled',
                'sync_frequency',
                'sync_cron_expression',
                'sync_time',
                'sync_timezone',
                'last_sync_at',
                'next_sync_at',
                'sync_options',
                'sync_stats',
            ]);
        });
    }
};
