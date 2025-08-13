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
        Schema::create('service_ticket_agent', function (Blueprint $table) {
            $table->id();
            $table->uuid('ticket_id');
            $table->uuid('user_id');
            $table->string('role')->default('assignee'); // assignee, reviewer, collaborator, etc.
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('unassigned_at')->nullable();
            $table->text('assignment_notes')->nullable();
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Ensure a user can only be assigned to a ticket once per role
            $table->unique(['ticket_id', 'user_id', 'role']);
            
            // Indexes for performance
            $table->index('ticket_id');
            $table->index('user_id');
            $table->index(['ticket_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_ticket_agent');
    }
};
