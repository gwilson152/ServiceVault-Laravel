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
        Schema::table('email_processing_logs', function (Blueprint $table) {
            // Command processing tracking fields
            $table->integer('commands_processed')->nullable()->after('processing_duration_ms')
                ->comment('Number of commands found in email subject');
            $table->integer('commands_executed')->nullable()->after('commands_processed')
                ->comment('Number of commands successfully executed');
            $table->integer('commands_failed')->nullable()->after('commands_executed')
                ->comment('Number of commands that failed execution');
            
            // Command processing status
            $table->boolean('command_processing_completed')->default(false)->after('commands_failed')
                ->comment('Whether command processing completed');
            $table->boolean('command_processing_success')->nullable()->after('command_processing_completed')
                ->comment('Whether command processing was successful');
            $table->text('command_processing_error')->nullable()->after('command_processing_success')
                ->comment('Error message if command processing failed');
            
            // Detailed command results
            $table->json('command_results')->nullable()->after('command_processing_error')
                ->comment('Detailed results of each command executed');
            
            // Command processing counts for better querying
            $table->integer('commands_processed_count')->nullable()->after('command_results')
                ->comment('Count of processed commands');
            $table->integer('commands_executed_count')->nullable()->after('commands_processed_count')
                ->comment('Count of successfully executed commands');
            $table->integer('commands_failed_count')->nullable()->after('commands_executed_count')
                ->comment('Count of failed commands');
            
            // Indexes for performance
            $table->index(['command_processing_completed', 'command_processing_success'], 'idx_command_processing_status');
            $table->index(['commands_executed_count'], 'idx_commands_executed');
            $table->index(['commands_failed_count'], 'idx_commands_failed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_processing_logs', function (Blueprint $table) {
            $table->dropIndex('idx_command_processing_status');
            $table->dropIndex('idx_commands_executed');
            $table->dropIndex('idx_commands_failed');
            
            $table->dropColumn([
                'commands_processed',
                'commands_executed', 
                'commands_failed',
                'command_processing_completed',
                'command_processing_success',
                'command_processing_error',
                'command_results',
                'commands_processed_count',
                'commands_executed_count',
                'commands_failed_count',
            ]);
        });
    }
};