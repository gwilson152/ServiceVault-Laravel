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
        // Import templates table
        Schema::create('import_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('source_type', ['database', 'api', 'file']);
            $table->json('default_configuration')->nullable();
            $table->json('field_mappings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['source_type', 'is_active']);
        });

        // Import profiles table (with UUID primary key)
        Schema::create('import_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('template_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('source_type', ['database', 'api', 'file']);
            $table->json('connection_config')->nullable();
            $table->json('configuration')->nullable();
            $table->enum('import_mode', ['sync', 'append', 'update'])->default('sync');
            $table->boolean('enable_scheduling')->default(false);
            $table->enum('schedule_frequency', ['hourly', 'daily', 'weekly', 'monthly'])->nullable();
            $table->time('schedule_time')->nullable();
            $table->json('schedule_days')->nullable(); // For weekly scheduling
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('next_sync_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('import_templates')->onDelete('cascade');
            $table->index(['source_type', 'is_active']);
            $table->index(['enable_scheduling', 'next_sync_at']);
        });

        // Import jobs table
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('profile_id');
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'canceled'])->default('pending');
            $table->enum('mode', ['sync', 'append', 'update'])->default('sync');
            $table->json('mode_config')->nullable();
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->integer('successful_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->integer('skipped_records')->default(0);
            $table->integer('updated_records')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration')->nullable(); // seconds
            $table->uuid('started_by')->nullable();
            $table->timestamps();

            $table->foreign('profile_id')->references('id')->on('import_profiles')->onDelete('cascade');
            $table->foreign('started_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['profile_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // Import mappings table
        Schema::create('import_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('profile_id');
            $table->string('source_field');
            $table->string('destination_table');
            $table->string('destination_field');
            $table->enum('data_type', ['string', 'integer', 'decimal', 'boolean', 'date', 'datetime', 'json']);
            $table->string('transformation')->nullable(); // Function name for data transformation
            $table->json('transformation_config')->nullable();
            $table->boolean('is_required')->default(false);
            $table->string('default_value')->nullable();
            $table->timestamps();

            $table->foreign('profile_id')->references('id')->on('import_profiles')->onDelete('cascade');
            $table->index(['profile_id', 'destination_table']);
        });

        // Import queries table (for database imports)
        Schema::create('import_queries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('profile_id');
            $table->string('name');
            $table->text('query');
            $table->string('target_table');
            $table->json('parameters')->nullable();
            $table->integer('execution_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('profile_id')->references('id')->on('import_profiles')->onDelete('cascade');
            $table->index(['profile_id', 'execution_order']);
        });

        // Import records table (tracking individual record imports)
        Schema::create('import_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('job_id');
            $table->string('external_id')->nullable();
            $table->string('record_type'); // 'user', 'ticket', 'account', etc.
            $table->uuid('local_id')->nullable(); // ID in local system after import
            $table->enum('status', ['pending', 'success', 'failed', 'skipped']);
            $table->text('error_message')->nullable();
            $table->json('source_data')->nullable();
            $table->json('transformed_data')->nullable();
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('import_jobs')->onDelete('cascade');
            $table->index(['job_id', 'status']);
            $table->index(['external_id', 'record_type']);
            $table->index(['local_id', 'record_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_records');
        Schema::dropIfExists('import_queries');
        Schema::dropIfExists('import_mappings');
        Schema::dropIfExists('import_jobs');
        Schema::dropIfExists('import_profiles');
        Schema::dropIfExists('import_templates');
    }
};