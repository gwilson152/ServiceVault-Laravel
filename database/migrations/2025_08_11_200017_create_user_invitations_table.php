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
        Schema::create('user_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email');
            $table->string('token')->unique();
            $table->uuid('account_id');
            $table->uuid('invited_by');
            $table->uuid('role_template_id')->nullable();
            $table->enum('status', ['pending', 'accepted', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_template_id')->references('id')->on('role_templates')->onDelete('set null');
            
            // Indexes
            $table->index(['email', 'status']);
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_invitations');
    }
};