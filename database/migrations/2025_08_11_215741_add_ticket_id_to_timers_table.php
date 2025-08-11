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
        Schema::table('timers', function (Blueprint $table) {
            // Add ticket_id foreign key column
            $table->uuid('ticket_id')->nullable()->after('account_id');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            
            // Add index for efficient querying
            $table->index(['user_id', 'ticket_id', 'status']);
            
            // Note: Unique constraint for active timers will be enforced in application logic
            // since we need conditional constraint based on status (running/paused)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timers', function (Blueprint $table) {
            // Remove constraints and indexes first
            $table->dropIndex(['user_id', 'ticket_id', 'status']);
            $table->dropForeign(['ticket_id']);
            $table->dropColumn('ticket_id');
        });
    }
};
