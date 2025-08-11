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
            $table->id();
            $table->foreignId('role_template_id')->constrained()->onDelete('cascade');
            $table->string('widget_id');
            $table->boolean('enabled')->default(true);
            $table->json('widget_config')->nullable(); // Custom widget settings
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->unique(['role_template_id', 'widget_id']);
            $table->index(['role_template_id', 'enabled']);
            $table->index('display_order');
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