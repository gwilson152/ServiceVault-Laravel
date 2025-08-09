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
        // Create role_template_user pivot table
        Schema::create('role_template_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_template_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Ensure one role template per user per account
            $table->unique(['user_id', 'role_template_id', 'account_id']);
        });

        // Create service_tickets table (with soft deletes included)
        Schema::create('service_tickets', function (Blueprint $table) {
            $table->id();
            
            // Account relationship (required - tickets belong to accounts)
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            
            // Ticket identification and basic info
            $table->string('ticket_number')->unique(); // Auto-generated (e.g., TKT-2025-001)
            $table->string('title');
            $table->text('description');
            
            // Customer information  
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            
            // Ticket workflow and priority
            $table->enum('status', [
                'open', 'in_progress', 'waiting_customer', 'resolved', 'closed', 'cancelled'
            ])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('category', [
                'support', 'maintenance', 'development', 'consulting', 'other'
            ])->default('support');
            
            // Assignment and ownership
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            
            // SLA and time tracking
            $table->timestamp('due_date')->nullable();
            $table->timestamp('sla_breached_at')->nullable();
            $table->integer('estimated_hours')->nullable(); // Estimated time in hours
            $table->boolean('billable')->default(true);
            
            // Communication tracking
            $table->timestamp('last_customer_update')->nullable();
            $table->timestamp('last_internal_update')->nullable();
            $table->text('resolution_notes')->nullable();
            
            // Billing information
            $table->foreignId('billing_rate_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('quoted_amount', 10, 2)->nullable(); // If quoted upfront
            $table->boolean('requires_approval')->default(false); // For high-value tickets
            
            // Status workflow tracking
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('started_at')->nullable(); // When work actually began
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            // Additional metadata
            $table->json('metadata')->nullable(); // For extensibility
            $table->text('internal_notes')->nullable(); // Staff-only notes
            
            $table->timestamps();
            $table->softDeletes(); // Include soft deletes
            
            // Indexes for performance
            $table->index(['account_id', 'status']);
            $table->index(['account_id', 'created_at']);
            $table->index(['assigned_to', 'status']);
            $table->index(['status', 'priority']);
            $table->index(['due_date', 'status']);
            $table->index(['customer_email']);
            $table->index(['ticket_number']);
        });

        // Create service_ticket_user pivot table
        Schema::create('service_ticket_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();
            $table->text('assignment_notes')->nullable();
            $table->timestamps();
            
            // Prevent duplicate assignments
            $table->unique(['service_ticket_id', 'user_id']);
            
            // Indexes for performance
            $table->index(['service_ticket_id', 'assigned_at']);
            $table->index(['user_id', 'assigned_at']);
        });

        // Add service_ticket_id to timers table
        Schema::table('timers', function (Blueprint $table) {
            $table->foreignId('service_ticket_id')
                ->nullable()
                ->after('billing_rate_id')
                ->constrained('service_tickets')
                ->onDelete('set null');
                
            // Add index for service ticket queries
            $table->index(['service_ticket_id', 'started_at']);
        });
        
        // Add service_ticket_id to time_entries table and soft deletes
        Schema::table('time_entries', function (Blueprint $table) {
            $table->foreignId('service_ticket_id')
                ->nullable()
                ->after('billing_rate_id')
                ->constrained('service_tickets')
                ->onDelete('set null');
                
            // Add index for service ticket queries
            $table->index(['service_ticket_id', 'started_at']);
            
            // Add soft deletes
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys and columns from existing tables
        Schema::table('timers', function (Blueprint $table) {
            $table->dropForeign(['service_ticket_id']);
            $table->dropIndex(['service_ticket_id', 'started_at']);
            $table->dropColumn('service_ticket_id');
        });
        
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropForeign(['service_ticket_id']);
            $table->dropIndex(['service_ticket_id', 'started_at']);
            $table->dropColumn('service_ticket_id');
            $table->dropSoftDeletes();
        });

        // Drop new tables (in reverse dependency order)
        Schema::dropIfExists('service_ticket_user');
        Schema::dropIfExists('service_tickets');
        Schema::dropIfExists('role_template_user');
    }
};