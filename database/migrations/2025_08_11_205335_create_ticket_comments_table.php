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
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('user_id');
            $table->text('content');
            $table->boolean('is_internal')->default(false); // internal vs customer-facing
            $table->json('attachments')->nullable(); // file attachment metadata
            $table->uuid('parent_id')->nullable(); // for reply threading
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['ticket_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['parent_id']);
        });

        // Add self-referencing foreign key constraint after table creation
        Schema::table('ticket_comments', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('ticket_comments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
    }
};
