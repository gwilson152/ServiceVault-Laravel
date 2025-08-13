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
        Schema::table('users', function (Blueprint $table) {
            // Add user_type to distinguish between Agents (service providers) and Account Users (customers)
            // Agents: Internal users who provide services and log time entries
            // Account Users: External customers who submit tickets but don't log time
            $table->enum('user_type', ['agent', 'account_user'])->default('account_user')->after('account_id');
            
            // Add index for efficient filtering
            $table->index(['user_type', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['user_type', 'account_id']);
            $table->dropColumn('user_type');
        });
    }
};