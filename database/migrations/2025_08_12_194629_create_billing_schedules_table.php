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
        Schema::create('billing_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('created_by_user_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('frequency', ['weekly', 'monthly', 'quarterly', 'annually']);
            $table->integer('interval')->default(1); // Every N periods
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_billing_date');
            $table->date('last_billed_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_send')->default(false);
            $table->integer('payment_terms')->default(30); // Days
            $table->json('billing_items')->nullable(); // Predefined items/rates
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes
            $table->index(['account_id', 'is_active']);
            $table->index('next_billing_date');
            $table->index(['frequency', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_schedules');
    }
};
