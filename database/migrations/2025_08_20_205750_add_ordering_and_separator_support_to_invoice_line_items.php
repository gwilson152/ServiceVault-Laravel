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
        Schema::table('invoice_line_items', function (Blueprint $table) {
            // Add ordering support
            $table->integer('sort_order')->default(0)->after('line_type')
                  ->comment('Sort order for line items (0-based)');
            
            // Add index for efficient ordering queries
            $table->index(['invoice_id', 'sort_order']);
        });
        
        // Add separator as a valid line_type (no schema change needed, just documentation)
        // line_type can now be: 'time_entry', 'addon', 'manual', 'separator'
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_line_items', function (Blueprint $table) {
            $table->dropIndex(['invoice_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
