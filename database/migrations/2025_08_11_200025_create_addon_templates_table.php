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
        Schema::create('addon_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // hardware, software, service, etc.
            $table->string('unit_type')->default('each'); // each, hour, day, etc.
            $table->decimal('default_price', 10, 2)->default(0);
            $table->decimal('default_quantity', 8, 2)->default(1);
            $table->boolean('is_taxable')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->uuid('account_id')->nullable();
            $table->boolean('is_system')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            
            // Indexes
            $table->index(['category', 'is_active']);
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addon_templates');
    }
};