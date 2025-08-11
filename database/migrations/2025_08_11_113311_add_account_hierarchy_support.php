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
            $table->unsignedBigInteger('parent_account_id')->nullable()->after('id');
            $table->unsignedBigInteger('root_account_id')->nullable()->after('parent_account_id'); 
            $table->integer('hierarchy_level')->default(0)->after('root_account_id');
            $table->string('hierarchy_path')->nullable()->after('hierarchy_level'); // "/1/5/12" for path traversal
            
            $table->foreign('parent_account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('root_account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->index(['root_account_id', 'hierarchy_level']);
            $table->index('hierarchy_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['parent_account_id']);
            $table->dropForeign(['root_account_id']);
            $table->dropIndex(['root_account_id', 'hierarchy_level']);
            $table->dropIndex(['hierarchy_path']);
            $table->dropColumn(['parent_account_id', 'root_account_id', 'hierarchy_level', 'hierarchy_path']);
        });
    }
};