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
        Schema::create('email_system_config', function (Blueprint $table) {
            $table->id();
            
            // Application-wide email configuration (single row)
            $table->string('configuration_name')->default('Application Email Configuration');
            
            // === INCOMING EMAIL SERVICE ===
            $table->boolean('incoming_enabled')->default(false);
            $table->string('incoming_provider')->nullable(); // 'imap', 'exchange', 'gmail_api', 'outlook_api'
            $table->string('incoming_host')->nullable();
            $table->integer('incoming_port')->nullable();
            $table->string('incoming_username')->nullable();
            $table->string('incoming_password')->nullable();
            $table->enum('incoming_encryption', ['tls', 'ssl', 'starttls', 'none'])->nullable();
            $table->string('incoming_folder')->default('INBOX');
            $table->json('incoming_settings')->nullable(); // Provider-specific settings
            
            // === OUTGOING EMAIL SERVICE ===
            $table->boolean('outgoing_enabled')->default(false);
            $table->string('outgoing_provider')->nullable(); // 'smtp', 'ses', 'sendgrid', 'postmark', 'mailgun'
            $table->string('outgoing_host')->nullable();
            $table->integer('outgoing_port')->nullable();
            $table->string('outgoing_username')->nullable();
            $table->string('outgoing_password')->nullable();
            $table->enum('outgoing_encryption', ['tls', 'ssl', 'starttls', 'none'])->nullable();
            $table->json('outgoing_settings')->nullable(); // Provider-specific settings
            
            // === FROM ADDRESS CONFIGURATION ===
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->string('reply_to_address')->nullable();
            
            // === SYSTEM STATUS ===
            $table->boolean('system_active')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->json('test_results')->nullable(); // Last test results for both services
            
            // === PROCESSING SETTINGS ===
            $table->boolean('auto_create_tickets')->default(true);
            $table->boolean('process_commands')->default(true);
            $table->boolean('send_confirmations')->default(true);
            $table->integer('max_retries')->default(3);
            $table->json('processing_rules')->nullable(); // Email processing configuration
            
            // === METADATA ===
            $table->uuid('updated_by_id')->nullable();
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');
            
            $table->timestamps();
        });
        
        // Insert default configuration row
        DB::table('email_system_config')->insert([
            'configuration_name' => 'Default Email System Configuration',
            'incoming_enabled' => false,
            'outgoing_enabled' => false,
            'system_active' => false,
            'auto_create_tickets' => true,
            'process_commands' => true,
            'send_confirmations' => true,
            'max_retries' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_system_config');
    }
};