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
        Schema::table('email_system_config', function (Blueprint $table) {
            // Email processing strategy fields
            $table->boolean('enable_email_processing')->default(true)->after('system_active');
            $table->boolean('auto_create_users')->default(true)->after('auto_create_tickets');
            
            // Unmapped domain strategy
            $table->enum('unmapped_domain_strategy', [
                'create_account',
                'assign_default_account', 
                'queue_for_review',
                'reject'
            ])->default('assign_default_account')->after('auto_create_users');
            
            $table->string('default_account_id')->nullable()->after('unmapped_domain_strategy');
            $table->string('default_role_template_id')->nullable()->after('default_account_id');
            
            // User creation settings
            $table->boolean('require_email_verification')->default(true)->after('default_role_template_id');
            $table->boolean('require_admin_approval')->default(true)->after('require_email_verification');
            
            // Add indexes for foreign key-like fields
            $table->index('default_account_id');
            $table->index('default_role_template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_system_config', function (Blueprint $table) {
            $table->dropIndex(['default_account_id']);
            $table->dropIndex(['default_role_template_id']);
            
            $table->dropColumn([
                'enable_email_processing',
                'auto_create_users',
                'unmapped_domain_strategy',
                'default_account_id',
                'default_role_template_id',
                'require_email_verification',
                'require_admin_approval',
            ]);
        });
    }
};