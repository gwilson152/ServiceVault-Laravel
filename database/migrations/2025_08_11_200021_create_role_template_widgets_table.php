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
        Schema::create('role_template_widgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('role_template_id');
            $table->uuid('widget_permission_id');
            $table->json('widget_config')->nullable();
            $table->boolean('enabled_by_default')->default(true);
            $table->boolean('is_configurable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('role_template_id')->references('id')->on('role_templates')->onDelete('cascade');
            $table->foreign('widget_permission_id')->references('id')->on('widget_permissions')->onDelete('cascade');
            
            // Indexes
            $table->unique(['role_template_id', 'widget_permission_id'], 'role_widget_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_template_widgets');
    }
};