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
        // Settings table (flexible key-value configuration)
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->string('type')->default('system'); // system, account, user
            $table->uuid('account_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['type', 'key']);
            $table->index(['account_id', 'type']);
            $table->index(['user_id', 'type']);
        });

        // Themes table
        Schema::create('themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('configuration');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'is_default']);
        });

        // Custom fields table (for extensible entities)
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type'); // 'ticket', 'account', 'user', etc.
            $table->string('name');
            $table->string('label');
            $table->enum('type', ['text', 'textarea', 'select', 'multiselect', 'checkbox', 'date', 'number']);
            $table->json('options')->nullable(); // For select/multiselect
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['entity_type', 'is_active']);
            $table->index(['entity_type', 'sort_order']);
        });

        // Custom field values table
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('custom_field_id');
            $table->uuid('entity_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('custom_field_id')->references('id')->on('custom_fields')->onDelete('cascade');
            $table->unique(['custom_field_id', 'entity_id']);
            $table->index(['entity_id']);
        });

        // Notifications table
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
        });

        // Activity log table (audit trail)
        Schema::create('activity_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('event_type');
            $table->string('entity_type')->nullable();
            $table->uuid('entity_id')->nullable();
            $table->text('description');
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['user_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['event_type', 'created_at']);
        });

        // System logs table
        Schema::create('system_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('level', ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug']);
            $table->string('channel')->nullable();
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->timestamps();

            $table->index(['level', 'created_at']);
            $table->index(['channel', 'created_at']);
        });

        // File attachments table
        Schema::create('file_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type'); // 'ticket', 'comment', etc.
            $table->uuid('entity_id');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->string('path');
            $table->uuid('uploaded_by');
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['entity_type', 'entity_id']);
            $table->index(['uploaded_by', 'created_at']);
        });

        // Webhooks table (for integrations)
        Schema::create('webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('url');
            $table->json('events'); // Array of events to listen for
            $table->string('secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('headers')->nullable();
            $table->timestamps();

            $table->index(['is_active']);
        });

        // Webhook calls table (audit log for webhook deliveries)
        Schema::create('webhook_calls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('webhook_id');
            $table->string('event_type');
            $table->json('payload');
            $table->integer('http_status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('cascade');
            $table->index(['webhook_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_calls');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('file_attachments');
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
        Schema::dropIfExists('themes');
        Schema::dropIfExists('settings');
    }
};