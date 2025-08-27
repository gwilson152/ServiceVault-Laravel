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
        // Invoices table
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number')->unique();
            $table->uuid('account_id');
            $table->enum('status', ['draft', 'pending', 'sent', 'paid', 'overdue', 'canceled'])->default('draft');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->date('invoice_date');
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->json('billing_address')->nullable();
            $table->enum('tax_application_mode', ['inclusive', 'exclusive'])->default('exclusive');
            $table->boolean('override_tax')->default(false);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->index(['account_id', 'status']);
            $table->index(['status', 'due_date']);
        });

        // Invoice line items table
        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->enum('type', ['time_entry', 'ticket_addon', 'custom']);
            $table->uuid('billable_id')->nullable(); // time_entry_id or ticket_addon_id
            $table->string('billable_type')->nullable();
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->boolean('taxable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_separator')->default(false);
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->index(['invoice_id', 'sort_order']);
            $table->index(['billable_type', 'billable_id']);
        });

        // Payments table
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'check', 'credit_card', 'bank_transfer', 'other']);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('payment_date');
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->index(['invoice_id', 'payment_date']);
        });

        // Billing schedules table
        Schema::create('billing_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->enum('frequency', ['weekly', 'monthly', 'quarterly', 'annually']);
            $table->integer('day_of_period')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('next_generation_at')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->index(['is_active', 'next_generation_at']);
        });

        // Billing settings table
        Schema::create('billing_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_name');
            $table->text('company_address')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('invoice_prefix', 10)->default('INV-');
            $table->integer('next_invoice_number')->default(1000);
            $table->integer('default_payment_terms')->default(30);
            $table->text('default_notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->decimal('master_tax_rate', 5, 4)->nullable();
            $table->enum('master_tax_type', ['percentage', 'fixed'])->default('percentage');
            $table->string('master_tax_name')->nullable();
            $table->timestamps();
        });

        // Tax configurations table
        Schema::create('tax_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id')->nullable(); // null for global tax settings
            $table->string('name');
            $table->decimal('rate', 5, 4); // e.g., 8.25% = 0.0825
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->index(['account_id', 'is_active']);
        });

        // Add unique constraints to prevent double billing
        Schema::table('invoice_line_items', function (Blueprint $table) {
            // Ensure time entries and ticket addons can only be billed once
            $table->unique(['billable_type', 'billable_id'], 'unique_billable_item');
        });

        // Add foreign keys back to billable items (after invoice_line_items creation)
        Schema::table('time_entries', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });

        Schema::table('ticket_addons', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_addons', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        Schema::dropIfExists('tax_configurations');
        Schema::dropIfExists('billing_settings');
        Schema::dropIfExists('billing_schedules');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_line_items');
        Schema::dropIfExists('invoices');
    }
};