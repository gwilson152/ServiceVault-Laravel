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
        Schema::create('tax_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id')->nullable(); // Null for global settings
            $table->string('name');
            $table->string('jurisdiction'); // State, Province, Country
            $table->decimal('tax_rate', 5, 4);
            $table->string('tax_type'); // 'sales', 'vat', 'gst', 'other'
            $table->string('tax_number')->nullable();
            $table->boolean('is_compound')->default(false); // Compound tax
            $table->boolean('is_active')->default(true);
            $table->json('applicable_categories')->nullable(); // Which addon categories this applies to
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            // Indexes
            $table->index(['account_id', 'is_active']);
            $table->index('jurisdiction');
            $table->index(['effective_date', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_configurations');
    }
};
