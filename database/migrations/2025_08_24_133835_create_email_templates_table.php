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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            
            // Template Identity
            $table->string('name'); // Human-readable name
            $table->string('key')->unique(); // Unique identifier for code reference
            $table->enum('template_type', [
                'ticket_created',
                'ticket_updated', 
                'ticket_assigned',
                'ticket_resolved',
                'comment_added',
                'command_confirmation',
                'command_error',
                'auto_response',
                'welcome',
                'custom'
            ])->index();
            
            // Template Content
            $table->string('subject');
            $table->longText('body_text'); // Plain text version
            $table->longText('body_html')->nullable(); // HTML version
            
            // Template Configuration
            $table->json('variables')->nullable(); // Available template variables
            $table->json('conditions')->nullable(); // When to use this template
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('priority')->default(100);
            
            // Localization
            $table->string('language', 10)->default('en');
            $table->string('timezone')->nullable();
            
            // Content Settings
            $table->boolean('include_signature')->default(true);
            $table->boolean('include_footer')->default(true);
            $table->boolean('auto_html_from_text')->default(true);
            
            // Usage Tracking
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            
            // Metadata
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->uuid('created_by_id')->nullable();
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            $table->uuid('updated_by_id')->nullable();
            $table->foreign('updated_by_id')->references('id')->on('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['account_id', 'template_type', 'is_active']);
            $table->index(['template_type', 'is_default']);
            $table->index(['language', 'is_active']);
            $table->unique(['account_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
