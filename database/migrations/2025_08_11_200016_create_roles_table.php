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
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('role_template_id');
            $table->string('name')->nullable();
            $table->json('custom_permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('role_template_id')->references('id')->on('role_templates')->onDelete('cascade');
            
            // Indexes
            $table->index(['account_id', 'is_active']);
            $table->unique(['account_id', 'role_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};