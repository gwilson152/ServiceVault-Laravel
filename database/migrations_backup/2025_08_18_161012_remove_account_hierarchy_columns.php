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
        Schema::table('accounts', function (Blueprint $table) {
            // Drop foreign key constraints if they exist
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['parent_account_id']);
            $table->dropForeign(['root_account_id']);
        });

        // Drop hierarchy-related columns (this will automatically drop associated indexes)
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn([
                'parent_id',
                'parent_account_id',
                'root_account_id',
                'hierarchy_level',
                'hierarchy_path',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            // Recreate hierarchy columns
            $table->uuid('parent_id')->nullable();
            $table->uuid('parent_account_id')->nullable();
            $table->uuid('root_account_id')->nullable();
            $table->integer('hierarchy_level')->default(0);
            $table->string('hierarchy_path')->nullable();

            // Recreate indexes
            $table->index(['parent_id', 'is_active']);

            // Recreate foreign key constraints
            $table->foreign('parent_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('parent_account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('root_account_id')->references('id')->on('accounts')->onDelete('set null');
        });
    }
};
