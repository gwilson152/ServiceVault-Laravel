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
        Schema::table('import_profiles', function (Blueprint $table) {
            // Add foreign key constraint for template_id
            $table->foreign('template_id')->references('id')->on('import_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_profiles', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });
    }
};
