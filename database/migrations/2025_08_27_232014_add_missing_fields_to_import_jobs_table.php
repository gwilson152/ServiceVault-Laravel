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
        Schema::table('import_jobs', function (Blueprint $table) {
            // Add legacy field name aliases that the ImportJob model expects
            $table->integer('records_processed')->default(0)->after('processed_records');
            $table->integer('records_imported')->default(0)->after('records_processed');
            $table->integer('records_updated')->default(0)->after('records_imported');
            $table->integer('records_skipped')->default(0)->after('records_updated');
            $table->integer('records_failed')->default(0)->after('records_skipped');
            $table->json('summary')->nullable()->after('errors');
            $table->integer('progress_percentage')->default(0)->after('summary');
            $table->string('current_operation')->nullable()->after('progress_percentage');
            $table->uuid('created_by')->nullable()->after('current_operation');
        });
        
        // Copy data from new field names to legacy field names for compatibility
        DB::statement('UPDATE import_jobs SET 
            records_processed = processed_records,
            records_imported = successful_records,
            records_updated = updated_records,
            records_skipped = skipped_records,
            records_failed = failed_records,
            created_by = started_by
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'records_processed',
                'records_imported', 
                'records_updated',
                'records_skipped',
                'records_failed',
                'summary',
                'progress_percentage',
                'current_operation',
                'created_by'
            ]);
        });
    }
};