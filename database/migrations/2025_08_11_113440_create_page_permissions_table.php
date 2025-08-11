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
        Schema::create('page_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('page_route'); // 'settings.roles', 'reports.billing'
            $table->string('page_name'); // 'Roles & Permissions', 'Billing Reports'
            $table->string('permission_key'); // 'pages.settings.roles'
            $table->json('required_permissions'); // Traditional permissions needed
            $table->string('category'); // 'settings', 'reports', 'admin'
            $table->string('context'); // 'service_provider', 'account_user', 'both'
            $table->boolean('is_menu_item')->default(true);
            $table->integer('menu_order')->default(0);
            $table->timestamps();
            
            $table->unique('page_route');
            $table->index(['category', 'context']);
            $table->index('permission_key');
            $table->index(['is_menu_item', 'menu_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_permissions');
    }
};