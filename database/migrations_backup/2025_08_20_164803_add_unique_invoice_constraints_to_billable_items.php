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
        // Add unique constraint to time_entries - each time entry can only be on one invoice
        Schema::table('time_entries', function (Blueprint $table) {
            // Only add index if invoice_id is not null (allows multiple unbilled entries)
            $table->index(['invoice_id'], 'time_entries_invoice_id_index');
        });

        // Add unique constraint to ticket_addons - each addon can only be on one invoice
        Schema::table('ticket_addons', function (Blueprint $table) {
            // Only add index if invoice_id is not null (allows multiple unbilled addons)
            $table->index(['invoice_id'], 'ticket_addons_invoice_id_index');
        });

        // Add database check constraints to ensure no duplicate billing
        // This prevents the same item from being on multiple invoices at the database level
        DB::statement('
            CREATE OR REPLACE FUNCTION prevent_duplicate_billing() RETURNS TRIGGER AS $$
            BEGIN
                -- For time entries, ensure no existing invoice_id when setting a new one
                IF TG_TABLE_NAME = \'time_entries\' AND NEW.invoice_id IS NOT NULL THEN
                    IF OLD.invoice_id IS NOT NULL AND OLD.invoice_id != NEW.invoice_id THEN
                        RAISE EXCEPTION \'Time entry % is already associated with invoice %. Cannot associate with multiple invoices.\', NEW.id, OLD.invoice_id;
                    END IF;
                END IF;

                -- For ticket addons, ensure no existing invoice_id when setting a new one  
                IF TG_TABLE_NAME = \'ticket_addons\' AND NEW.invoice_id IS NOT NULL THEN
                    IF OLD.invoice_id IS NOT NULL AND OLD.invoice_id != NEW.invoice_id THEN
                        RAISE EXCEPTION \'Ticket addon % is already associated with invoice %. Cannot associate with multiple invoices.\', NEW.id, OLD.invoice_id;
                    END IF;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Create triggers to enforce the constraint
        DB::statement('
            CREATE TRIGGER time_entries_prevent_duplicate_billing
            BEFORE UPDATE ON time_entries
            FOR EACH ROW EXECUTE FUNCTION prevent_duplicate_billing();
        ');

        DB::statement('
            CREATE TRIGGER ticket_addons_prevent_duplicate_billing  
            BEFORE UPDATE ON ticket_addons
            FOR EACH ROW EXECUTE FUNCTION prevent_duplicate_billing();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the triggers
        DB::statement('DROP TRIGGER IF EXISTS time_entries_prevent_duplicate_billing ON time_entries;');
        DB::statement('DROP TRIGGER IF EXISTS ticket_addons_prevent_duplicate_billing ON ticket_addons;');
        DB::statement('DROP FUNCTION IF EXISTS prevent_duplicate_billing();');

        // Remove the indexes
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropIndex('time_entries_invoice_id_index');
        });

        Schema::table('ticket_addons', function (Blueprint $table) {
            $table->dropIndex('ticket_addons_invoice_id_index');
        });
    }
};
