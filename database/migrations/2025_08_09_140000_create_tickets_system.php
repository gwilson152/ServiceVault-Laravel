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
        // Create tickets table (renamed from service_tickets)
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            
            // Basic ticket information
            $table->string('ticket_number')->unique(); // Auto-generated (e.g., TKT-2025-001)
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->string('status', 50)->default('open'); // References ticket_statuses.key
            $table->string('category', 50)->nullable(); // References ticket_categories.key
            
            // Relationships
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by_id')->constrained('users')->onDelete('cascade');
            
            // Time and billing
            $table->integer('estimated_hours')->nullable();
            $table->decimal('estimated_amount', 10, 2)->nullable();
            $table->decimal('actual_amount', 10, 2)->nullable();
            $table->foreignId('billing_rate_id')->nullable()->constrained()->onDelete('set null');
            
            // Workflow tracking
            $table->timestamp('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable(); // Custom fields, tags, etc.
            $table->json('settings')->nullable(); // Ticket-specific settings
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['account_id', 'status', 'created_at']);
            $table->index(['assigned_to_id', 'status']);
            $table->index(['created_by_id', 'created_at']);
            $table->index(['ticket_number']);
            $table->index(['status', 'priority', 'created_at']);
            $table->index(['due_date', 'status']);
        });

        // Create ticket_user pivot table for team assignments
        Schema::create('ticket_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('assignee'); // assignee, watcher, collaborator
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('unassigned_at')->nullable();
            $table->timestamps();
            
            $table->unique(['ticket_id', 'user_id']);
            $table->index(['ticket_id', 'role']);
            $table->index(['user_id', 'assigned_at']);
            $table->index(['ticket_id', 'assigned_at']);
        });

        // Create ticket_statuses table
        Schema::create('ticket_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique(); // e.g., 'open', 'in_progress'
            $table->string('name', 100); // e.g., 'Open', 'In Progress'
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6B7280'); // Hex color code
            $table->string('bg_color', 7)->default('#F3F4F6'); // Background color
            $table->string('icon', 50)->default('DocumentTextIcon'); // Heroicon name
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Default status for new tickets
            $table->boolean('is_closed')->default(false); // Indicates ticket completion
            $table->boolean('is_billable')->default(true); // Time can be billed in this status
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable(); // Workflow rules, notifications, etc.
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
            $table->index(['is_default', 'is_active']);
        });

        // Create ticket_categories table
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique(); // e.g., 'support', 'maintenance'
            $table->string('name', 100); // e.g., 'Technical Support', 'System Maintenance'
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6B7280'); // Hex color code
            $table->string('bg_color', 7)->default('#F3F4F6'); // Background color
            $table->string('icon', 50)->default('DocumentTextIcon'); // Heroicon name
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Default category for new tickets
            $table->boolean('requires_approval')->default(false); // Category requires manager approval
            $table->decimal('default_priority_multiplier', 3, 2)->default(1.00); // Affects default priority
            $table->integer('default_estimated_hours')->nullable(); // Default time estimate
            $table->integer('sla_hours')->nullable(); // SLA hours for this category
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable(); // Additional configuration
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
            $table->index(['requires_approval', 'is_active']);
        });

        // Create addon_templates table
        Schema::create('addon_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50); // e.g., 'hardware', 'software', 'service'
            $table->string('unit_type', 50)->default('each'); // each, hours, days, months
            $table->decimal('default_price', 10, 2)->default(0.00);
            $table->integer('default_quantity')->default(1);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('configuration')->nullable(); // Template-specific settings
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index(['is_active', 'name']);
        });

        // Create ticket_addons table
        Schema::create('ticket_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('addon_template_id')->nullable()->constrained()->onDelete('set null');
            
            // Addon details (can override template values)
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->string('unit_type', 50)->default('each');
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->integer('quantity')->default(1);
            $table->decimal('discount_percent', 5, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->boolean('is_taxable')->default(true);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            
            // Calculated fields
            $table->decimal('subtotal', 10, 2)->default(0.00); // unit_price * quantity
            $table->decimal('discount_total', 10, 2)->default(0.00); // Applied discount
            $table->decimal('tax_amount', 10, 2)->default(0.00); // Calculated tax
            $table->decimal('total', 10, 2)->default(0.00); // Final total
            
            // Workflow
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('approval_notes')->nullable();
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['ticket_id', 'status']);
            $table->index(['addon_template_id']);
            $table->index(['status', 'created_at']);
        });

        // Add ticket_id to timers table (update existing table)
        Schema::table('timers', function (Blueprint $table) {
            $table->foreignId('ticket_id')
                ->nullable()
                ->after('user_id')
                ->constrained('tickets')
                ->onDelete('cascade');
            
            $table->index(['ticket_id', 'started_at']);
        });

        // Add ticket_id to time_entries table (update existing table)
        Schema::table('time_entries', function (Blueprint $table) {
            $table->foreignId('ticket_id')
                ->nullable()
                ->after('user_id')
                ->constrained('tickets')
                ->onDelete('cascade');
                
            $table->index(['ticket_id', 'started_at']);
        });

        // Create role_template_user pivot table if not exists
        if (!Schema::hasTable('role_template_user')) {
            Schema::create('role_template_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('role_template_id')->constrained()->onDelete('cascade');
                $table->foreignId('account_id')->constrained()->onDelete('cascade');
                $table->timestamps();
                
                // Ensure one role template per user per account
                $table->unique(['user_id', 'role_template_id', 'account_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key columns from existing tables
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropIndex(['ticket_id', 'started_at']);
            $table->dropColumn('ticket_id');
        });

        Schema::table('timers', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropIndex(['ticket_id', 'started_at']);
            $table->dropColumn('ticket_id');
        });

        // Drop new tables (in reverse dependency order)
        Schema::dropIfExists('ticket_addons');
        Schema::dropIfExists('addon_templates');
        Schema::dropIfExists('ticket_categories');
        Schema::dropIfExists('ticket_statuses');
        Schema::dropIfExists('ticket_user');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('role_template_user');
    }
};