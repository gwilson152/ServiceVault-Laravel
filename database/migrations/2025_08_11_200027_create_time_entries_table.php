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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('account_id')->nullable();
            $table->uuid('ticket_id')->nullable();
            $table->uuid('billing_rate_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration'); // in seconds
            $table->decimal('rate_at_time', 8, 2)->nullable();
            $table->decimal('billed_amount', 10, 2)->nullable();
            $table->boolean('billable')->default(true);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
            $table->foreign('billing_rate_id')->references('id')->on('billing_rates')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['account_id', 'billable']);
            $table->index(['ticket_id', 'billable']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};