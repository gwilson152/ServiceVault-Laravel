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
            // Import behavior configuration
            $table->enum('import_mode', ['create', 'update', 'upsert'])
                ->default('upsert')
                ->after('configuration')
                ->comment('How to handle existing records: create=skip, update=only update, upsert=create or update');
            
            // Duplicate detection configuration
            $table->json('duplicate_detection')->nullable()->after('import_mode')->comment('Rules for detecting duplicate records');
            $table->boolean('skip_duplicates')->default(false)->after('duplicate_detection')->comment('Skip processing duplicates entirely');
            $table->boolean('update_duplicates')->default(true)->after('skip_duplicates')->comment('Update existing records when found');
            
            // Record linking and tracking
            $table->string('source_identifier_field')->nullable()->after('update_duplicates')->comment('Field to use as external identifier for linking');
            $table->json('matching_strategy')->nullable()->after('source_identifier_field')->comment('Strategy for matching existing records');
            
            // Import statistics tracking
            $table->json('import_stats')->nullable()->after('matching_strategy')->comment('Statistics from last import');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'import_mode',
                'duplicate_detection',
                'skip_duplicates', 
                'update_duplicates',
                'source_identifier_field',
                'matching_strategy',
                'import_stats'
            ]);
        });
    }
};