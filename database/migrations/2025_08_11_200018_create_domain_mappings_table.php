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
        Schema::create('domain_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('domain_pattern');
            $table->uuid('account_id');
            $table->uuid('role_template_id')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('role_template_id')->references('id')->on('role_templates')->onDelete('set null');
            
            // Indexes
            $table->index(['domain_pattern', 'is_active']);
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_mappings');
    }
};