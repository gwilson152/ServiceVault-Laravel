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
        Schema::table('email_processing_logs', function (Blueprint $table) {
            // Change processing_duration_ms from integer to bigInteger to handle large values
            $table->bigInteger('processing_duration_ms')->nullable()->change();
            
            // Also change retry_count to ensure consistency, though it's unlikely to overflow
            $table->bigInteger('retry_count')->default(0)->change();
            
            // Change command processing fields that might also overflow
            $table->bigInteger('commands_processed')->default(0)->change();
            $table->bigInteger('commands_executed_count')->default(0)->change();
            $table->bigInteger('commands_failed_count')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_processing_logs', function (Blueprint $table) {
            // Revert back to integer types
            $table->integer('processing_duration_ms')->nullable()->change();
            $table->integer('retry_count')->default(0)->change();
            $table->integer('commands_processed')->default(0)->change();
            $table->integer('commands_executed_count')->default(0)->change();
            $table->integer('commands_failed_count')->default(0)->change();
        });
    }
};
