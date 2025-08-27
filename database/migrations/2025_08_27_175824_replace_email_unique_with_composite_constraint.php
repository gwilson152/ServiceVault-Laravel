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
        // First, check for existing email duplicates before making the change
        $duplicates = \DB::table('users')
            ->select('email')
            ->whereNotNull('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->count() > 0) {
            throw new Exception(
                'Cannot proceed: Found existing duplicate emails. ' .
                'Please resolve duplicate emails before running this migration. ' .
                'Duplicates: ' . $duplicates->pluck('email')->implode(', ')
            );
        }

        // Check if email unique constraint exists
        $emailConstraintExists = \DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.table_constraints 
            WHERE table_name = 'users' 
              AND constraint_name = 'users_email_unique'
              AND constraint_type = 'UNIQUE'
        ")[0]->count > 0;

        if ($emailConstraintExists) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_email_unique');
            });
        }

        // Add composite unique constraint on email + user_type
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['email', 'user_type'], 'users_email_user_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the composite unique constraint
            $table->dropUnique('users_email_user_type_unique');
            
            // Restore the original email unique constraint
            $table->unique('email', 'users_email_unique');
        });
    }
};
