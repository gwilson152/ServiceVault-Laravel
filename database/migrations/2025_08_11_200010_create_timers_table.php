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
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('account_id')->nullable();
            $table->string('ticket_number')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['running', 'paused', 'stopped'])->default('running');
            $table->timestamp('started_at');
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->integer('duration')->default(0);
            $table->decimal('billing_rate', 8, 2)->nullable();
            $table->boolean('billable')->default(true);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['account_id', 'status']);
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