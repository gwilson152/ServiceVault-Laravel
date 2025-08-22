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
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('default_tax_rate', 5, 4)->default(0)->after('name')
                ->comment('Default tax rate percentage for this account (e.g., 8.25 for 8.25%)');
            $table->enum('default_tax_application_mode', ['all_items', 'non_service_items', 'custom'])
                ->default('all_items')->after('default_tax_rate')
                ->comment('Default tax application mode: all_items, non_service_items, or custom');
            $table->boolean('tax_exempt')->default(false)->after('default_tax_application_mode')
                ->comment('Whether this account is tax exempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['default_tax_rate', 'default_tax_application_mode', 'tax_exempt']);
        });
    }
};
