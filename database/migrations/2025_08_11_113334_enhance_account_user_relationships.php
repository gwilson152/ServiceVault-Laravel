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
        // Check if the table exists, create if it doesn't
        if (!Schema::hasTable('account_user')) {
            Schema::create('account_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('account_id')->constrained()->onDelete('cascade');
                $table->boolean('is_primary')->default(false);
                $table->json('account_permissions')->nullable(); // Account-specific permissions
                $table->timestamps();
                
                $table->unique(['user_id', 'account_id']);
                $table->index(['user_id', 'is_primary']);
            });
        } else {
            // Enhance existing table
            Schema::table('account_user', function (Blueprint $table) {
                if (!Schema::hasColumn('account_user', 'is_primary')) {
                    $table->boolean('is_primary')->default(false)->after('account_id');
                }
                if (!Schema::hasColumn('account_user', 'account_permissions')) {
                    $table->json('account_permissions')->nullable()->after('is_primary');
                }
                if (!Schema::hasColumn('account_user', 'created_at')) {
                    $table->timestamps();
                }
                
                // Add indexes if they don't exist
                try {
                    $table->index(['user_id', 'is_primary']);
                } catch (\Exception $e) {
                    // Index may already exist
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('account_user')) {
            Schema::table('account_user', function (Blueprint $table) {
                if (Schema::hasColumn('account_user', 'is_primary')) {
                    $table->dropColumn('is_primary');
                }
                if (Schema::hasColumn('account_user', 'account_permissions')) {
                    $table->dropColumn('account_permissions');
                }
                try {
                    $table->dropIndex(['user_id', 'is_primary']);
                } catch (\Exception $e) {
                    // Index may not exist
                }
            });
        }
    }
};