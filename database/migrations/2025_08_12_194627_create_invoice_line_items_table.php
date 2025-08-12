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
        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->uuid('time_entry_id')->nullable();
            $table->uuid('ticket_addon_id')->nullable();
            $table->string('line_type'); // 'time_entry', 'addon', 'manual'
            $table->string('description');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 4)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->boolean('is_billable')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->foreign('time_entry_id')->references('id')->on('time_entries')->onDelete('set null');
            $table->foreign('ticket_addon_id')->references('id')->on('ticket_addons')->onDelete('set null');
            
            // Indexes
            $table->index(['invoice_id', 'line_type']);
            $table->index('time_entry_id');
            $table->index('ticket_addon_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');
    }
};
