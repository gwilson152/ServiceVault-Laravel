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
        Schema::create('email_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('email_id')->unique(); // Unique identifier for each email
            $table->uuid('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreignId('email_config_id')->nullable()->constrained()->onDelete('set null');
            
            // Email Direction and Processing
            $table->enum('direction', ['incoming', 'outgoing'])->index();
            $table->enum('status', ['pending', 'processing', 'processed', 'failed', 'retry'])->default('pending')->index();
            
            // Email Metadata
            $table->string('from_address')->index();
            $table->json('to_addresses')->nullable(); // Multiple recipients
            $table->json('cc_addresses')->nullable();
            $table->json('bcc_addresses')->nullable();
            $table->string('subject')->nullable();
            $table->string('message_id')->nullable()->index(); // Email Message-ID header
            $table->string('in_reply_to')->nullable()->index(); // For threading
            $table->json('references')->nullable(); // Email reference chain
            
            // Processing Details
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->integer('processing_duration_ms')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();
            
            // Results and Actions Taken
            $table->json('actions_taken')->nullable(); // Commands processed, tickets created/updated
            $table->uuid('ticket_id')->nullable();
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
            $table->uuid('ticket_comment_id')->nullable();
            $table->foreign('ticket_comment_id')->references('id')->on('ticket_comments')->onDelete('set null');
            $table->boolean('created_new_ticket')->default(false);
            
            // Error Handling
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->text('error_stack_trace')->nullable();
            
            // Email Content (for debugging/audit)
            $table->longText('raw_email_content')->nullable();
            $table->text('parsed_body_text')->nullable();
            $table->text('parsed_body_html')->nullable();
            $table->json('attachments')->nullable(); // Attachment metadata
            
            // Queue and Job Information
            $table->string('queue_name')->nullable();
            $table->string('job_id')->nullable()->index();
            $table->integer('job_attempts')->default(0);
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['account_id', 'direction', 'status']);
            $table->index(['ticket_id', 'created_at']);
            $table->index(['status', 'next_retry_at']);
            $table->index(['created_at', 'direction']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_processing_logs');
    }
};
