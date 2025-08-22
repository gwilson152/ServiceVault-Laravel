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
        Schema::create('import_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('import_profiles')->onDelete('cascade');
            $table->string('source_table'); // Source PostgreSQL table name
            $table->string('destination_table'); // Destination Service Vault table name
            $table->json('field_mappings'); // Source field -> destination field mappings
            $table->text('where_conditions')->nullable(); // SQL WHERE conditions for filtering
            $table->json('transformation_rules')->nullable(); // Data transformation rules
            $table->boolean('is_active')->default(true);
            $table->integer('import_order')->default(0); // Order of import execution
            $table->timestamps();

            // Indexes
            $table->index(['profile_id', 'is_active']);
            $table->index(['source_table', 'destination_table']);
            $table->index('import_order');

            // Unique constraint to prevent duplicate mappings
            $table->unique(['profile_id', 'source_table', 'destination_table']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_mappings');
    }
};
