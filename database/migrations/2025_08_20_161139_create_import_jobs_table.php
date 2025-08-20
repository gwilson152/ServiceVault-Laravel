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
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('import_profiles')->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, running, completed, failed, cancelled
            $table->json('import_options')->nullable(); // Import configuration options
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('records_processed')->default(0);
            $table->integer('records_imported')->default(0);
            $table->integer('records_skipped')->default(0);
            $table->integer('records_failed')->default(0);
            $table->json('summary')->nullable(); // Detailed import summary
            $table->longText('errors')->nullable(); // Error log
            $table->integer('progress_percentage')->default(0);
            $table->string('current_operation')->nullable(); // Current import step
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index(['profile_id', 'status']);
            $table->index(['status', 'started_at']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
