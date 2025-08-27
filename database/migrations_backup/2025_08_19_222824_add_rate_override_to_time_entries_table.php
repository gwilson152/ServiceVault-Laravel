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
        Schema::table('time_entries', function (Blueprint $table) {
            // Add rate override field for manual rate adjustments
            $table->decimal('rate_override', 8, 2)->nullable()->after('rate_at_time');
            // Add approved amount field to lock the final calculated amount at approval
            $table->decimal('approved_amount', 10, 2)->nullable()->after('billed_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn(['rate_override', 'approved_amount']);
        });
    }
};
