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
        Schema::create('domain_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('domain_pattern')->index(); // e.g., '*.company.com', 'company.com'
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_template_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher number = higher priority
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['is_active', 'priority']);
            $table->unique(['domain_pattern', 'account_id']); // Prevent duplicate mappings
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_mappings');
    }
};
