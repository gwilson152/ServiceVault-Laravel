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
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('tax_application_mode', ['all_items', 'non_service_items', 'custom'])
                  ->default('all_items')
                  ->after('tax_rate')
                  ->comment('How tax should be applied: all_items (all taxable items), non_service_items (only products/addons), custom (per item settings)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('tax_application_mode');
        });
    }
};