<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            // Make account_id NOT NULL - all time entries must be associated with a customer account
            // This aligns with the Agent/Customer architecture where all billable work is for accounts
            $table->uuid('account_id')->nullable(false)->change();
        });
        
        // Create a database trigger to ensure ticket/account consistency
        // PostgreSQL doesn't support subqueries in CHECK constraints, so we use a trigger instead
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION check_time_entry_ticket_account_consistency()
            RETURNS TRIGGER AS $$
            BEGIN
                -- If ticket_id is provided, ensure it belongs to the same account
                IF NEW.ticket_id IS NOT NULL THEN
                    IF NOT EXISTS (
                        SELECT 1 FROM tickets 
                        WHERE tickets.id = NEW.ticket_id 
                        AND tickets.account_id = NEW.account_id
                    ) THEN
                        RAISE EXCEPTION 'Time entry ticket must belong to the same account as the time entry';
                    END IF;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
SQL
        );
        
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER time_entry_ticket_account_consistency_trigger
            BEFORE INSERT OR UPDATE ON time_entries
            FOR EACH ROW
            EXECUTE FUNCTION check_time_entry_ticket_account_consistency();
SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the trigger and function
        DB::unprepared('DROP TRIGGER IF EXISTS time_entry_ticket_account_consistency_trigger ON time_entries;');
        DB::unprepared('DROP FUNCTION IF EXISTS check_time_entry_ticket_account_consistency();');
        
        Schema::table('time_entries', function (Blueprint $table) {
            // Make account_id nullable again
            $table->uuid('account_id')->nullable()->change();
        });
    }
};