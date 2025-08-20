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
        Schema::table('import_profiles', function (Blueprint $table) {
            // Change type column to be more flexible (remove constraints)
            $table->string('type')->nullable()->change();
            
            // Add new columns for universal system
            $table->uuid('template_id')->nullable()->after('type');
            $table->string('database_type', 50)->default('postgresql')->after('template_id');
            $table->text('notes')->nullable()->after('description');
            
            // Add indexes for new columns
            $table->index('database_type');
            $table->index('template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_profiles', function (Blueprint $table) {
            // Restore type constraints
            $table->string('type')->change();
            
            // Remove new columns
            $table->dropColumn(['template_id', 'database_type', 'notes']);
            
            // Restore type index if needed
            $table->index('type');
        });
    }
};
