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
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticket_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->string('status', 50)->default('open');
            $table->string('category', 50)->nullable();

            // Relationships
            $table->uuid('account_id');
            $table->uuid('agent_id')->nullable(); // Internal staff assigned to work on ticket
            $table->uuid('customer_id')->nullable(); // Registered user who needs the service
            $table->string('customer_name')->nullable(); // External customer name
            $table->string('customer_email')->nullable(); // External customer email
            $table->uuid('created_by_id'); // Who created the ticket record

            // Time and billing
            $table->integer('estimated_hours')->nullable();
            $table->decimal('estimated_amount', 10, 2)->nullable();
            $table->decimal('actual_amount', 10, 2)->nullable();
            $table->uuid('billing_rate_id')->nullable();

            // Workflow tracking
            $table->timestamp('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('billing_rate_id')->references('id')->on('billing_rates')->onDelete('set null');

            // Indexes
            $table->index(['account_id', 'status']);
            $table->index('ticket_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
