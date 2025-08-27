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
        // Email configurations table (account-specific email settings)
        Schema::create('email_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('name');
            $table->enum('type', ['imap', 'pop3', 'exchange'])->default('imap');
            $table->string('host');
            $table->integer('port')->default(993);
            $table->enum('encryption', ['ssl', 'tls', 'none'])->default('ssl');
            $table->string('username');
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->index(['account_id', 'is_active']);
        });

        // Email processing logs table
        Schema::create('email_processing_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('email_config_id')->nullable();
            $table->string('email_id')->unique(); // External email ID for tracking
            $table->enum('direction', ['incoming', 'outgoing'])->default('incoming');
            $table->string('message_id')->nullable();
            $table->string('from_address');
            $table->json('to_addresses'); // Array of recipient addresses (required)
            $table->json('cc_addresses')->nullable(); // Array of CC addresses
            $table->json('bcc_addresses')->nullable(); // Array of BCC addresses
            $table->string('subject')->nullable();
            $table->string('in_reply_to')->nullable(); // In-Reply-To header
            $table->text('references')->nullable(); // References header
            $table->enum('status', ['pending', 'processing', 'processed', 'failed', 'ignored', 'retry'])->default('pending');
            $table->text('error_message')->nullable();
            $table->uuid('ticket_id')->nullable(); // Created or updated ticket
            $table->uuid('ticket_comment_id')->nullable(); // Created ticket comment
            $table->uuid('account_id')->nullable(); // Assigned account
            $table->uuid('user_id')->nullable(); // Identified user
            $table->json('headers')->nullable();
            $table->longText('body')->nullable();
            $table->longText('parsed_body_text')->nullable(); // Parsed plain text body
            $table->longText('parsed_body_html')->nullable(); // Parsed HTML body
            $table->longText('raw_email_content')->nullable(); // Full raw email content
            $table->json('attachments')->nullable();
            $table->timestamp('received_at')->nullable();
            
            // Processing tracking
            $table->boolean('created_new_ticket')->default(false);
            $table->timestamp('processed_at')->nullable(); // When processing completed
            $table->integer('processing_duration_ms')->nullable(); // Processing time in milliseconds
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            $table->json('actions_taken')->nullable(); // Actions taken during processing
            $table->json('error_details')->nullable(); // Structured error information
            $table->text('error_stack_trace')->nullable(); // Error stack trace for debugging
            
            // Command processing fields
            $table->boolean('is_command')->default(false);
            $table->string('command_type')->nullable();
            $table->json('command_data')->nullable();
            $table->enum('command_status', ['pending', 'processed', 'failed'])->nullable();
            $table->text('command_response')->nullable();
            $table->integer('commands_processed')->default(0); // Number of commands found
            $table->integer('commands_executed_count')->default(0); // Successfully executed commands
            $table->integer('commands_failed_count')->default(0); // Failed commands
            $table->boolean('command_processing_success')->default(false);
            
            $table->timestamps();

            $table->foreign('email_config_id')->references('id')->on('email_configs')->onDelete('set null');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['status', 'received_at']);
            $table->index(['from_address', 'status']);
            $table->index(['message_id']);
            $table->index(['is_command', 'command_status']);
        });

        // Email templates table
        Schema::create('email_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('subject');
            $table->longText('body');
            $table->enum('type', ['ticket_created', 'ticket_updated', 'ticket_assigned', 'custom']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        // Email system configuration table (unified email configuration)
        Schema::create('email_system_config', function (Blueprint $table) {
            $table->id();
            
            // Application-wide email configuration (single row)
            $table->string('configuration_name')->default('Application Email Configuration');
            
            // === INCOMING EMAIL SERVICE ===
            $table->boolean('incoming_enabled')->default(false);
            $table->string('incoming_provider')->nullable(); // 'imap', 'exchange', 'gmail_api', 'outlook_api', 'm365'
            $table->string('incoming_host')->nullable();
            $table->integer('incoming_port')->nullable();
            $table->string('incoming_username')->nullable();
            $table->string('incoming_password')->nullable();
            $table->enum('incoming_encryption', ['tls', 'ssl', 'starttls', 'none'])->nullable();
            $table->string('incoming_folder')->default('INBOX');
            $table->json('incoming_settings')->nullable(); // Provider-specific settings (M365, etc.)
            
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
            $table->boolean('enable_email_processing')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->json('test_results')->nullable(); // Last test results for both services
            
            // === PROCESSING SETTINGS ===
            $table->boolean('auto_create_tickets')->default(true);
            $table->boolean('auto_create_users')->default(true);
            $table->boolean('process_commands')->default(true);
            $table->boolean('send_confirmations')->default(true);
            $table->integer('max_retries')->default(3);
            $table->json('processing_rules')->nullable(); // Email processing configuration
            
            // === EMAIL PROCESSING & USER MANAGEMENT ===
            // Unmapped domain strategy
            $table->enum('unmapped_domain_strategy', [
                'create_account',
                'assign_default_account', 
                'queue_for_review',
                'reject'
            ])->default('assign_default_account');
            
            $table->string('default_account_id')->nullable();
            $table->string('default_role_template_id')->nullable();
            
            // User creation settings
            $table->boolean('require_email_verification')->default(true);
            $table->boolean('require_admin_approval')->default(true);
            
            // Processing options
            $table->string('timestamp_source')->default('original'); // 'service_vault' or 'original'
            $table->string('timestamp_timezone')->default('preserve'); // 'preserve', 'convert_local', 'convert_utc'
            
            // === METADATA ===
            $table->uuid('updated_by_id')->nullable();
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');
            
            // Indexes for performance
            $table->index('default_account_id');
            $table->index('default_role_template_id');
            
            $table->timestamps();
        });

        // Email domain mappings table
        Schema::create('email_domain_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('domain');
            $table->uuid('account_id');
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->unique(['domain', 'account_id']);
            $table->index(['domain', 'is_active']);
        });

        // Insert default email system configuration
        DB::table('email_system_config')->insert([
            'configuration_name' => 'Default Email System Configuration',
            'incoming_enabled' => false,
            'outgoing_enabled' => false,
            'system_active' => false,
            'enable_email_processing' => true,
            'auto_create_tickets' => true,
            'auto_create_users' => true,
            'process_commands' => true,
            'send_confirmations' => true,
            'max_retries' => 3,
            'unmapped_domain_strategy' => 'assign_default_account',
            'require_email_verification' => true,
            'require_admin_approval' => true,
            'timestamp_source' => 'original',
            'timestamp_timezone' => 'preserve',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_domain_mappings');
        Schema::dropIfExists('email_system_config');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('email_processing_logs');
        Schema::dropIfExists('email_configs');
    }
};