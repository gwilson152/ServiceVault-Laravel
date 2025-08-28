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
        Schema::table('email_domain_mappings', function (Blueprint $table) {
            // Add sort_order field for drag-and-drop ordering
            $table->integer('sort_order')->default(0)->after('is_active');
            
            // Create index for efficient ordering
            $table->index(['is_active', 'sort_order']);
        });
        
        // Set initial sort_order values - high priority first, then medium, then low
        // High priority mappings (if any)
        DB::statement("UPDATE email_domain_mappings SET sort_order = 100 WHERE priority = 'high'");
        
        // Medium priority mappings (if any) 
        DB::statement("UPDATE email_domain_mappings SET sort_order = 200 WHERE priority = 'medium'");
        
        // Low priority mappings (if any)
        DB::statement("UPDATE email_domain_mappings SET sort_order = 300 WHERE priority = 'low'");
        
        // Handle any mappings without priority
        DB::statement("UPDATE email_domain_mappings SET sort_order = 250 WHERE priority IS NULL");
        
        Schema::table('email_domain_mappings', function (Blueprint $table) {
            // Remove the priority field
            $table->dropColumn('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_domain_mappings', function (Blueprint $table) {
            // Add back priority field
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium')->after('account_id');
            
            // Remove sort_order
            $table->dropIndex(['is_active', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};