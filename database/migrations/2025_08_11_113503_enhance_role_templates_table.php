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
        Schema::table('role_templates', function (Blueprint $table) {
            // Add modifiability control
            if (!Schema::hasColumn('role_templates', 'is_modifiable')) {
                $table->boolean('is_modifiable')->default(true)->after('is_default');
            }
            
            // Add widget permissions
            if (!Schema::hasColumn('role_templates', 'widget_permissions')) {
                $table->json('widget_permissions')->nullable()->after('permissions');
            }
            
            // Add page permissions
            if (!Schema::hasColumn('role_templates', 'page_permissions')) {
                $table->json('page_permissions')->nullable()->after('widget_permissions');
            }
            
            // Add context specification (service_provider, account_user, both)
            if (!Schema::hasColumn('role_templates', 'context')) {
                $table->enum('context', ['service_provider', 'account_user', 'both'])->default('both')->after('description');
            }
            
            // Add indexes for performance
            $table->index('is_modifiable');
            $table->index('context');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_templates', function (Blueprint $table) {
            if (Schema::hasColumn('role_templates', 'is_modifiable')) {
                $table->dropIndex(['is_modifiable']);
                $table->dropColumn('is_modifiable');
            }
            
            if (Schema::hasColumn('role_templates', 'widget_permissions')) {
                $table->dropColumn('widget_permissions');
            }
            
            if (Schema::hasColumn('role_templates', 'page_permissions')) {
                $table->dropColumn('page_permissions');
            }
            
            if (Schema::hasColumn('role_templates', 'context')) {
                $table->dropIndex(['context']);
                $table->dropColumn('context');
            }
        });
    }
};