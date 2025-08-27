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
        Schema::create('role_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->enum('context', ['service_provider', 'account_user', 'both'])->default('both');
            $table->uuid('account_id')->nullable();
            $table->json('permissions')->nullable();
            $table->json('widget_permissions')->nullable();
            $table->json('page_permissions')->nullable();
            $table->json('dashboard_layout')->nullable();
            $table->boolean('is_system_role')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_modifiable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');

            // Indexes
            $table->index(['context', 'is_active']);
            $table->index('account_id');
        });

        // Add foreign key for users table role_template_id after role_templates table is created
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_template_id')->references('id')->on('role_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_templates');
    }
};
