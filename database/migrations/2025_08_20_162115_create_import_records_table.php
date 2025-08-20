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
            $table->id();
            $table->foreignId('job_id')->constrained('import_jobs')->onDelete('cascade');
            $table->foreignId('mapping_id')->constrained('import_mappings')->onDelete('cascade');
            $table->string('source_table'); // e.g., 'users' in FreeScout
            $table->string('source_id'); // Original ID in source system
            $table->string('destination_table'); // e.g., 'users' in Service Vault
            $table->string('destination_id'); // UUID in Service Vault
            $table->json('source_data')->nullable(); // Original record data for reference
            $table->string('import_status')->default('imported'); // imported, updated, failed
            $table->text('error_message')->nullable(); // Error details if failed
            $table->timestamps();
            
            // Indexes for efficient lookups
            $table->index(['job_id', 'import_status']);
            $table->index(['source_table', 'source_id']);
            $table->index(['destination_table', 'destination_id']);
            $table->unique(['job_id', 'source_table', 'source_id']); // Prevent duplicates per job
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
