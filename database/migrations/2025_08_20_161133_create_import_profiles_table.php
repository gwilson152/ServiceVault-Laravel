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
        Schema::create('import_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('postgresql'); // Import profile type
            $table->string('host');
            $table->integer('port')->default(5432);
            $table->string('database');
            $table->string('username');
            $table->text('password'); // Encrypted
            $table->string('ssl_mode')->default('prefer'); // PostgreSQL SSL modes
            $table->text('description')->nullable();
            $table->json('connection_options')->nullable(); // Additional PostgreSQL options
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamp('last_tested_at')->nullable();
            $table->json('last_test_result')->nullable(); // Connection test results
            $table->timestamps();

            // Indexes
            $table->index(['type', 'is_active']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_profiles');
    }
};
