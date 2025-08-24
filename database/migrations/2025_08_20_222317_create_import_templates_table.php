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
        Schema::create('import_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('platform', 100)->nullable(); // 'custom', 'generic', etc.
            $table->text('description')->nullable();
            $table->string('database_type', 50)->default('postgresql');
            $table->json('configuration'); // Template configuration with queries and mappings
            $table->boolean('is_system')->default(false); // System vs user templates
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('platform');
            $table->index('database_type');
            $table->index(['is_system', 'is_active']);
            $table->index('created_by');

            // Foreign key constraint
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_templates');
    }
};
