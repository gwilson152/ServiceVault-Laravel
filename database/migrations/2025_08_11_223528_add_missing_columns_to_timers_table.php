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
        Schema::table('timers', function (Blueprint $table) {
            // Add missing columns needed for timer functionality
            $table->string('device_id')->nullable()->after('stopped_at');
            $table->boolean('is_synced')->default(true)->after('device_id');
            $table->json('metadata')->nullable()->after('is_synced');
            $table->integer('total_paused_duration')->default(0)->after('metadata');
            
            // Replace the simple billing_rate column with billing_rate_id foreign key
            $table->dropColumn('billing_rate');
            $table->uuid('billing_rate_id')->nullable()->after('total_paused_duration');
            $table->foreign('billing_rate_id')->references('id')->on('billing_rates')->onDelete('set null');
            
            // Add missing columns for relationships (tasks table might not exist yet, so make nullable)
            $table->uuid('task_id')->nullable()->after('billing_rate_id');
            $table->uuid('time_entry_id')->nullable()->after('task_id');
            
            // Add indexes for performance
            $table->index(['device_id']);
            $table->index(['is_synced']);
            $table->index(['billing_rate_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timers', function (Blueprint $table) {
            // Remove foreign key constraints first
            $table->dropForeign(['billing_rate_id']);
            
            // Remove indexes
            $table->dropIndex(['device_id']);
            $table->dropIndex(['is_synced']);
            $table->dropIndex(['billing_rate_id']);
            
            // Remove columns
            $table->dropColumn([
                'device_id', 
                'is_synced', 
                'metadata', 
                'total_paused_duration',
                'billing_rate_id',
                'task_id',
                'time_entry_id'
            ]);
            
            // Add back the old billing_rate column
            $table->decimal('billing_rate', 8, 2)->nullable()->after('duration');
        });
    }
};
