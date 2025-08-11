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
        Schema::create('ticket_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique(); // system-wide unique key (open, in_progress, closed)
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6B7280'); // Hex color
            $table->string('bg_color', 7)->default('#F3F4F6'); // Background hex color
            $table->string('icon', 50)->nullable(); // Icon name
            $table->uuid('account_id')->nullable(); // null = system status
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_closed')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_billable')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            
            // Indexes
            $table->index(['account_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_statuses');
    }
};