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
        Schema::create('billing_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id')->nullable(); // Null for global settings
            $table->string('company_name');
            $table->text('company_address');
            $table->string('company_phone')->nullable();
            $table->string('company_email');
            $table->string('company_website')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('invoice_prefix')->default('INV');
            $table->integer('next_invoice_number')->default(1001);
            $table->integer('payment_terms')->default(30); // Days
            $table->string('currency', 3)->default('USD');
            $table->string('timezone')->default('UTC');
            $table->string('date_format')->default('Y-m-d');
            $table->json('payment_methods')->nullable(); // Enabled payment methods
            $table->boolean('auto_send_invoices')->default(false);
            $table->boolean('send_payment_reminders')->default(true);
            $table->json('reminder_schedule')->nullable(); // Days before/after due date
            $table->text('invoice_footer')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            // Indexes
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_settings');
    }
};
