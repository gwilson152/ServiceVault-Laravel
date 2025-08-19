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
        // Add invoice_id column to time_entries table
        Schema::table('time_entries', function (Blueprint $table) {
            $table->uuid('invoice_id')->nullable()->after('billing_rate_id');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');

            // Add index for unbilled items queries
            $table->index(['account_id', 'billable', 'invoice_id', 'status']);
        });

        // Add invoice_id column to ticket_addons table
        Schema::table('ticket_addons', function (Blueprint $table) {
            $table->uuid('invoice_id')->nullable()->after('billing_category');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');

            // Add index for unbilled items queries
            $table->index(['billable', 'invoice_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropIndex(['account_id', 'billable', 'invoice_id', 'status']);
            $table->dropColumn('invoice_id');
        });

        Schema::table('ticket_addons', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropIndex(['billable', 'invoice_id', 'status']);
            $table->dropColumn('invoice_id');
        });
    }
};
