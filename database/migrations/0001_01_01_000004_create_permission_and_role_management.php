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
        // Permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('group')->nullable();
            $table->timestamps();

            $table->index(['group', 'name']);
        });

        // Widget permissions table
        Schema::create('widget_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('widget_type')->nullable();
            $table->timestamps();

            $table->index(['widget_type']);
        });

        // Page permissions table
        Schema::create('page_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('page_route')->nullable();
            $table->timestamps();

            $table->index(['page_route']);
        });

        // Role templates table
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
            $table->index(['is_system_role', 'is_active']);
        });

        // Role template widgets (for widget permission assignment)
        Schema::create('role_template_widgets', function (Blueprint $table) {
            $table->id();
            $table->uuid('role_template_id');
            $table->unsignedBigInteger('widget_permission_id');
            $table->timestamps();

            $table->foreign('role_template_id')->references('id')->on('role_templates')->onDelete('cascade');
            $table->foreign('widget_permission_id')->references('id')->on('widget_permissions')->onDelete('cascade');
            $table->unique(['role_template_id', 'widget_permission_id']);
        });

        // Roles table (for individual user role assignments)
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('role_template_id');
            $table->json('custom_permissions')->nullable();
            $table->json('custom_widget_permissions')->nullable();
            $table->json('custom_page_permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_template_id')->references('id')->on('role_templates')->onDelete('cascade');
            $table->unique(['user_id', 'role_template_id']);
            $table->index(['user_id', 'is_active']);
        });

        // Add foreign key for role_template_id in users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_template_id')->references('id')->on('role_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_template_id']);
        });

        Schema::dropIfExists('roles');
        Schema::dropIfExists('role_template_widgets');
        Schema::dropIfExists('role_templates');
        Schema::dropIfExists('page_permissions');
        Schema::dropIfExists('widget_permissions');
        Schema::dropIfExists('permissions');
    }
};