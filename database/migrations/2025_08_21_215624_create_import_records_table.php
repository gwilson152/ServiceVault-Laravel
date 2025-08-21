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
        Schema::create('import_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('import_job_id')->index();
            $table->uuid('import_profile_id')->index();
            
            // Source record information
            $table->string('source_table')->index();
            $table->json('source_data'); // Original imported data
            $table->string('source_identifier')->nullable()->index(); // External ID/key
            $table->string('source_hash', 64)->index(); // Hash for duplicate detection
            
            // Target record information  
            $table->string('target_type')->index(); // users, tickets, time_entries, etc.
            $table->uuid('target_id')->nullable()->index(); // Service Vault record ID
            $table->enum('import_action', ['created', 'updated', 'skipped', 'failed'])->index();
            $table->enum('import_mode', ['create', 'update', 'upsert'])->index();
            
            // Duplicate detection and matching
            $table->json('matching_rules')->nullable(); // Rules used for duplicate detection
            $table->json('matching_fields')->nullable(); // Fields that matched
            $table->uuid('duplicate_of')->nullable()->index(); // Links to existing import record
            
            // Error tracking
            $table->text('error_message')->nullable();
            $table->json('validation_errors')->nullable();
            
            // Metadata
            $table->json('field_mappings')->nullable(); // Field mappings used
            $table->json('transformations')->nullable(); // Transformations applied
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('import_job_id')->references('id')->on('import_jobs')->onDelete('cascade');
            $table->foreign('import_profile_id')->references('id')->on('import_profiles')->onDelete('cascade');
            $table->foreign('duplicate_of')->references('id')->on('import_records')->onDelete('set null');
            
            // Indexes for performance
            $table->index(['target_type', 'target_id']);
            $table->index(['source_table', 'source_identifier']);
            $table->index(['import_profile_id', 'source_hash']);
            $table->index(['import_job_id', 'import_action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_records');
    }
};