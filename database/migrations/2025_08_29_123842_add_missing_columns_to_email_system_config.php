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
        Schema::table('email_system_config', function (Blueprint $table) {
            // Add post-processing action settings
            $table->enum('post_processing_action', ['none', 'mark_read', 'move_to_folder', 'delete'])->default('none')->after('timestamp_timezone');
            $table->string('move_to_folder_id')->nullable()->after('post_processing_action');
            $table->string('move_to_folder_name')->nullable()->after('move_to_folder_id');
            
            // Add email retrieval mode setting
            $table->enum('email_retrieval_mode', ['unread_only', 'all', 'recent'])->default('unread_only')->after('move_to_folder_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_system_config', function (Blueprint $table) {
            $table->dropColumn([
                'post_processing_action',
                'move_to_folder_id', 
                'move_to_folder_name',
                'email_retrieval_mode'
            ]);
        });
    }
};
