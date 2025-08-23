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
            $table->json('configuration')->nullable()->after('connection_options')->comment('Custom query configuration for visual query builder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_profiles', function (Blueprint $table) {
            $table->dropColumn('configuration');
        });
    }
};
