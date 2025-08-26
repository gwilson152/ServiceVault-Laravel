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
        Schema::create('email_domain_mappings', function (Blueprint $table) {
            $table->id();
            
            // Domain/Email Pattern Matching
            $table->string('domain_pattern'); // e.g., '@acme.com', 'support@acme.com', '*@acme.com'
            $table->enum('pattern_type', ['domain', 'email', 'wildcard'])->default('domain');
            
            // Business Account Assignment
            $table->uuid('account_id');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            
            // Assignment Rules
            $table->uuid('default_assigned_user_id')->nullable(); // Default agent for new tickets
            $table->foreign('default_assigned_user_id')->references('id')->on('users')->onDelete('set null');
            $table->string('default_category')->nullable(); // Default ticket category
            $table->string('default_priority')->default('medium'); // Default ticket priority
            
            // Processing Rules
            $table->boolean('auto_create_tickets')->default(true);
            $table->boolean('send_auto_reply')->default(false);
            $table->text('auto_reply_template')->nullable();
            $table->json('custom_rules')->nullable(); // Additional processing rules
            
            // Status and Priority
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(100); // Higher number = higher priority for matching
            
            // Metadata
            $table->uuid('created_by_id')->nullable();
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['domain_pattern', 'is_active']);
            $table->index(['account_id', 'is_active']);
            $table->index(['priority', 'is_active']);
            
            // Unique constraint to prevent duplicate domain patterns
            $table->unique(['domain_pattern', 'pattern_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_domain_mappings');
    }
};