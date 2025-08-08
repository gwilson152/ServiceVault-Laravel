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
        Schema::create('timers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('task_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('billing_rate_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('time_entry_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('description')->nullable();
            $table->enum('status', ['running', 'paused', 'stopped'])->default('running');
            
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->integer('total_paused_duration')->default(0); // in seconds
            
            // Cross-device sync fields
            $table->string('device_id')->nullable();
            $table->boolean('is_synced')->default(true);
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'started_at']);
            $table->index(['project_id', 'started_at']);
            $table->index('device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timers');
    }
};