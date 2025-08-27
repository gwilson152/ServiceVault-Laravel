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
        Schema::create('import_queries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('profile_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('base_table'); // Primary table for the query
            $table->json('joins')->nullable(); // Array of JOIN definitions
            $table->text('where_conditions')->nullable(); // SQL WHERE clause
            $table->json('select_fields')->nullable(); // Specific fields to select
            $table->text('order_by')->nullable();
            $table->integer('limit_clause')->nullable();
            $table->string('destination_table'); // Service Vault destination table
            $table->json('field_mappings'); // Source -> destination field mappings
            $table->json('transformation_rules')->nullable(); // Data transformation rules
            $table->json('validation_rules')->nullable(); // Data validation rules
            $table->integer('import_order')->default(0); // Order of query execution
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['profile_id', 'is_active']);
            $table->index(['base_table', 'destination_table']);
            $table->index('import_order');
            $table->index('name');

            // Foreign key constraints
            $table->foreign('profile_id')->references('id')->on('import_profiles')->onDelete('cascade');

            // Unique constraint for query names per profile
            $table->unique(['profile_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_queries');
    }
};
