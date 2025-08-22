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
            // Add mode configuration field
            $table->json('mode_config')->nullable()->after('import_options')
                ->comment('Import mode configuration used for this job');

            // Add records updated counter
            $table->integer('records_updated')->default(0)->after('records_imported')
                ->comment('Number of records that were updated during import');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_jobs', function (Blueprint $table) {
            $table->dropColumn(['mode_config', 'records_updated']);
        });
    }
};
