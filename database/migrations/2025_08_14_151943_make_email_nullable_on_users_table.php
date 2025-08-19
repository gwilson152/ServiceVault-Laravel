<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop existing unique constraint
            $table->dropUnique(['email']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Make email column nullable
            $table->string('email')->nullable()->change();
        });

        // Create partial unique constraint in PostgreSQL (unique only when not null)
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE email IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the partial unique index
        DB::statement('DROP INDEX IF EXISTS users_email_unique');

        Schema::table('users', function (Blueprint $table) {
            // Make email not nullable
            $table->string('email')->nullable(false)->change();
            // Add back the regular unique constraint
            $table->unique('email');
        });
    }
};
