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
        // Billing rates table (required by other tables)
        Schema::create('billing_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('rate', 10, 2);
            $table->uuid('account_id')->nullable();
            $table->uuid('user_id')->nullable(); // User-specific billing rates
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['account_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
            $table->index(['is_default', 'is_active']);
        });

        // Ticket categories table
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique(); // system-wide unique key (support, billing, technical)
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6B7280'); // Hex color
            $table->string('bg_color', 7)->default('#F3F4F6'); // Background hex color
            $table->string('icon', 50)->nullable(); // Icon name
            $table->uuid('account_id')->nullable(); // null = system category
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->decimal('default_priority_multiplier', 3, 2)->default(1.00);
            $table->integer('default_estimated_hours')->nullable();
            $table->integer('sla_hours')->nullable(); // SLA in hours
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            // Indexes
            $table->index(['account_id', 'is_active']);
            $table->index(['is_active', 'sort_order']);
        });

        // Ticket statuses table
        Schema::create('ticket_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique(); // system-wide unique key (open, in_progress, closed)
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6B7280'); // Hex color
            $table->string('bg_color', 7)->default('#F3F4F6'); // Background hex color
            $table->string('icon', 50)->nullable(); // Icon name
            $table->uuid('account_id')->nullable(); // null = system status
            $table->enum('type', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_closed')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('billable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            // Indexes
            $table->index(['account_id', 'is_active']);
            $table->index(['type', 'is_active']);
            $table->index(['is_active', 'sort_order']);
        });

        // Ticket priorities table
        Schema::create('ticket_priorities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique(); // low, normal, medium, high, urgent
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->default('#6B7280'); // Text color
            $table->string('bg_color')->default('#F3F4F6'); // Background color
            $table->string('icon')->nullable(); // Icon name
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('level')->default(1); // Keep existing level field
            $table->integer('severity_level')->default(1); // 1=lowest, 5=highest
            $table->decimal('escalation_multiplier', 3, 2)->default(1.00); // SLA multiplier
            $table->integer('escalation_hours')->nullable(); // Keep existing field
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['level', 'is_active']);
            $table->index(['is_active', 'sort_order']);
        });

        // Tickets table
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_id')->nullable()->unique();
            $table->string('ticket_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->uuid('account_id');
            $table->uuid('customer_id')->nullable();
            $table->uuid('agent_id')->nullable();
            $table->uuid('created_by_id'); // Who created the ticket record
            $table->uuid('category_id')->nullable();
            $table->uuid('status_id')->nullable();
            $table->uuid('priority_id')->nullable();
            
            // Customer information (for external customers)
            $table->string('customer_name')->nullable(); // External customer name
            $table->string('customer_email')->nullable(); // External customer email
            
            // Time and billing estimates
            $table->integer('estimated_hours')->nullable();
            $table->decimal('estimated_amount', 10, 2)->nullable();
            $table->decimal('actual_amount', 10, 2)->nullable();
            $table->uuid('billing_rate_id')->nullable();
            
            // Workflow tracking
            $table->timestamp('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            // Flexible data storage
            $table->json('custom_fields')->nullable();
            $table->json('metadata')->nullable();
            $table->json('settings')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('agent_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('ticket_categories')->onDelete('set null');
            $table->foreign('status_id')->references('id')->on('ticket_statuses')->onDelete('set null');
            $table->foreign('priority_id')->references('id')->on('ticket_priorities')->onDelete('set null');
            $table->foreign('billing_rate_id')->references('id')->on('billing_rates')->onDelete('set null');

            $table->index(['account_id', 'status']);
            $table->index(['agent_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'priority']);
        });

        // Ticket comments table
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_id')->nullable()->unique();
            $table->uuid('ticket_id');
            $table->uuid('user_id');
            $table->text('content');
            $table->uuid('parent_id')->nullable(); // For comment threading
            $table->boolean('is_internal')->default(false); // Match model field
            $table->json('attachments')->nullable();
            $table->timestamp('edited_at')->nullable(); // For edit tracking
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('ticket_comments')->onDelete('cascade');
            $table->index(['ticket_id', 'parent_id']);
            $table->index(['user_id', 'is_internal']);
            $table->index(['parent_id']);
        });

        // Ticket addons table
        Schema::create('ticket_addons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('added_by_user_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // product, service, expense, license, hardware
            $table->string('sku')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->boolean('billable')->default(true);
            $table->boolean('is_taxable')->default(true);
            $table->string('billing_category')->nullable();
            $table->uuid('addon_template_id')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->uuid('approved_by_user_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('invoice_id')->nullable(); // Keep for backward compatibility
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('added_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by_user_id')->references('id')->on('users')->onDelete('set null');
            // Note: addon_template_id foreign key will be added after addon_templates table is created
            
            $table->index(['ticket_id', 'billable']);
            $table->index(['status']);
            $table->index(['category']);
            $table->index(['added_by_user_id']);
            $table->index(['approved_by_user_id']);
        });

        // Ticket agents pivot table
        Schema::create('ticket_agent', function (Blueprint $table) {
            $table->id();
            $table->uuid('ticket_id');
            $table->uuid('agent_id');
            $table->enum('role', ['primary', 'collaborator'])->default('collaborator');
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['ticket_id', 'agent_id']);
        });

        // Categories table (general purpose)
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('type')->default('general');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        // Addon templates table
        Schema::create('addon_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('default_amount', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active']);
        });

        // Add foreign key constraint for addon_template_id after addon_templates table is created
        Schema::table('ticket_addons', function (Blueprint $table) {
            $table->foreign('addon_template_id')->references('id')->on('addon_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addon_templates');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('ticket_agent');
        Schema::dropIfExists('ticket_addons');
        Schema::dropIfExists('ticket_comments');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_priorities');
        Schema::dropIfExists('ticket_statuses');
        Schema::dropIfExists('ticket_categories');
        Schema::dropIfExists('billing_rates');
    }
};