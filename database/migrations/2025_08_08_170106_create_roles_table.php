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
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->foreignId('role_template_id')->constrained('role_templates')->onDelete('cascade');
            $table->string('name')->nullable(); // Optional custom name for this role instance
            $table->json('custom_permissions')->nullable(); // Account-specific permission overrides
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Ensure unique role template per account
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
