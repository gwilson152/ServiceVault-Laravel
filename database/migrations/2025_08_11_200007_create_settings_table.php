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
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key');
            $table->text('value')->nullable();
            $table->string('type')->default('user'); // system, user, account
            $table->uuid('account_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes and constraints
            $table->unique(['key', 'account_id', 'user_id']);
            $table->index(['type', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};