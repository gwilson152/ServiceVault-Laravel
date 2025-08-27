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
        // Timers table
        Schema::create('timers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('account_id');
            $table->uuid('ticket_id')->nullable();
            $table->string('description');
            $table->enum('status', ['running', 'paused', 'canceled', 'committed'])->default('running');
            $table->timestamp('started_at');
            $table->timestamp('paused_at')->nullable();
            $table->integer('duration')->default(0); // seconds
            $table->uuid('billing_rate_id')->nullable();
            $table->decimal('rate_override', 10, 2)->nullable();
            $table->uuid('time_entry_id')->nullable(); // Link to created time entry when committed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
            $table->foreign('billing_rate_id')->references('id')->on('billing_rates')->onDelete('set null');

            $table->index(['user_id', 'status']);
            $table->index(['account_id', 'status']);
            $table->index(['status', 'started_at']);
        });

        // Time entries table
        Schema::create('time_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('external_id')->nullable()->unique();
            $table->uuid('user_id');
            $table->uuid('account_id'); // Required, not nullable
            $table->uuid('ticket_id')->nullable();
            $table->string('description');
            $table->integer('duration'); // minutes
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->uuid('billing_rate_id')->nullable();
            $table->decimal('rate_at_time', 8, 2)->nullable(); // Rate at time of entry
            $table->decimal('rate_override', 10, 2)->nullable();
            $table->decimal('billed_amount', 10, 2)->nullable(); // Final billed amount
            $table->boolean('billable')->default(true);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->uuid('invoice_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('timer_id')->nullable(); // Link to source timer if created from timer
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
            $table->foreign('billing_rate_id')->references('id')->on('billing_rates')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('timer_id')->references('id')->on('timers')->onDelete('set null');

            $table->index(['user_id', 'billable']);
            $table->index(['account_id', 'billable']);
            $table->index(['ticket_id', 'billable']);
            $table->index(['status']);
            $table->index(['started_at', 'billable']);
        });

        // Add foreign key from timers to time_entries (for committed timers)
        Schema::table('timers', function (Blueprint $table) {
            $table->foreign('time_entry_id')->references('id')->on('time_entries')->onDelete('set null');
        });

        // Create PostgreSQL trigger to ensure ticket belongs to same account as time entry
        DB::statement("
            CREATE OR REPLACE FUNCTION validate_time_entry_ticket_account()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.ticket_id IS NOT NULL THEN
                    IF NOT EXISTS (
                        SELECT 1 FROM tickets 
                        WHERE id = NEW.ticket_id 
                        AND account_id = NEW.account_id
                    ) THEN
                        RAISE EXCEPTION 'Ticket must belong to the same account as the time entry';
                    END IF;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER time_entry_ticket_account_check
            BEFORE INSERT OR UPDATE ON time_entries
            FOR EACH ROW
            EXECUTE FUNCTION validate_time_entry_ticket_account();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS time_entry_ticket_account_check ON time_entries');
        DB::statement('DROP FUNCTION IF EXISTS validate_time_entry_ticket_account()');
        
        Schema::table('timers', function (Blueprint $table) {
            $table->dropForeign(['time_entry_id']);
        });
        
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('timers');
    }
};