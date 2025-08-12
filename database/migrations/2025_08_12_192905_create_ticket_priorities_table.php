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
        Schema::create('ticket_priorities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique(); // low, normal, medium, high, urgent
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color')->default('#6B7280'); // Text color
            $table->string('bg_color')->default('#F3F4F6'); // Background color
            $table->string('icon')->nullable(); // Icon name
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('severity_level')->default(1); // 1=lowest, 5=highest
            $table->decimal('escalation_multiplier', 3, 2)->default(1.00); // SLA multiplier
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_priorities');
    }
};
