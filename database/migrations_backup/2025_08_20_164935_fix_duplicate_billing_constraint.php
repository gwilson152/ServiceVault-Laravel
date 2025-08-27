<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing triggers and function
        DB::statement('DROP TRIGGER IF EXISTS time_entries_prevent_duplicate_billing ON time_entries;');
        DB::statement('DROP TRIGGER IF EXISTS ticket_addons_prevent_duplicate_billing ON ticket_addons;');
        DB::statement('DROP FUNCTION IF EXISTS prevent_duplicate_billing();');

        // Create improved function that prevents reassignment once invoiced
        DB::statement('
            CREATE OR REPLACE FUNCTION prevent_duplicate_billing() RETURNS TRIGGER AS $$
            BEGIN
                -- For time entries, prevent changing invoice_id if it\'s already set
                IF TG_TABLE_NAME = \'time_entries\' THEN
                    IF OLD.invoice_id IS NOT NULL AND NEW.invoice_id IS NOT NULL AND OLD.invoice_id != NEW.invoice_id THEN
                        RAISE EXCEPTION \'Time entry % is already associated with invoice %. Cannot reassign to invoice %.\', 
                            NEW.id, OLD.invoice_id, NEW.invoice_id;
                    END IF;
                END IF;

                -- For ticket addons, prevent changing invoice_id if it\'s already set
                IF TG_TABLE_NAME = \'ticket_addons\' THEN
                    IF OLD.invoice_id IS NOT NULL AND NEW.invoice_id IS NOT NULL AND OLD.invoice_id != NEW.invoice_id THEN
                        RAISE EXCEPTION \'Ticket addon % is already associated with invoice %. Cannot reassign to invoice %.\', 
                            NEW.id, OLD.invoice_id, NEW.invoice_id;
                    END IF;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Recreate triggers with the improved function
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

        // Restore original function
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

        // Restore original triggers
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
};
