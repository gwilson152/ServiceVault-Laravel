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
        Schema::create('widget_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('widget_id'); // 'system-health', 'ticket-overview'
            $table->string('permission_key'); // 'widgets.dashboard.system-health'
            $table->string('category'); // 'dashboard', 'pages', 'reports'
            $table->string('context'); // 'service_provider', 'account_user', 'both'
            $table->json('required_permissions'); // Traditional permissions needed
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->unique(['widget_id', 'permission_key']);
            $table->index(['category', 'context']);
            $table->index('widget_id');
            $table->index('permission_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('widget_permissions');
    }
};