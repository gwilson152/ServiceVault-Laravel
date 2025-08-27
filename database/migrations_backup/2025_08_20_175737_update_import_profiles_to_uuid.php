<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, check if import_profiles table exists and has data
        if (! Schema::hasTable('import_profiles')) {
            return;
        }

        // Check if the table has any records
        $hasRecords = DB::table('import_profiles')->exists();

        if ($hasRecords) {
            // If there are records, we need to handle the conversion carefully

            // 1. Add a new UUID column
            Schema::table('import_profiles', function (Blueprint $table) {
                $table->uuid('new_id')->nullable();
            });

            // 2. Generate UUIDs for existing records
            $profiles = DB::table('import_profiles')->get();
            foreach ($profiles as $profile) {
                DB::table('import_profiles')
                    ->where('id', $profile->id)
                    ->update(['new_id' => (string) Str::uuid()]);
            }

            // 3. Update foreign key references in all dependent tables
            $dependentTables = ['import_jobs', 'import_mappings'];

            foreach ($dependentTables as $tableName) {
                if (Schema::hasTable($tableName)) {
                    // Add new UUID foreign key column
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->uuid('new_profile_id')->nullable();
                    });

                    // Update the foreign key references
                    $records = DB::table($tableName)
                        ->join('import_profiles', $tableName.'.profile_id', '=', 'import_profiles.id')
                        ->select($tableName.'.id as record_id', 'import_profiles.new_id as new_profile_id')
                        ->get();

                    foreach ($records as $record) {
                        DB::table($tableName)
                            ->where('id', $record->record_id)
                            ->update(['new_profile_id' => $record->new_profile_id]);
                    }

                    // Drop old foreign key and rename new one
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->dropForeign(['profile_id']);
                        $table->dropColumn('profile_id');
                    });

                    Schema::table($tableName, function (Blueprint $table) {
                        $table->renameColumn('new_profile_id', 'profile_id');
                    });
                }
            }

            // 4. Update the primary key in import_profiles
            Schema::table('import_profiles', function (Blueprint $table) {
                $table->dropPrimary(['id']);
                $table->dropColumn('id');
            });

            Schema::table('import_profiles', function (Blueprint $table) {
                $table->renameColumn('new_id', 'id');
                $table->primary('id');
            });

            // 5. Re-add the foreign key constraints for all dependent tables
            foreach ($dependentTables as $tableName) {
                if (Schema::hasTable($tableName)) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->foreign('profile_id')->references('id')->on('import_profiles')->onDelete('cascade');
                    });
                }
            }
        } else {
            // If no records, we can simply recreate the table structure
            Schema::table('import_profiles', function (Blueprint $table) {
                $table->dropPrimary(['id']);
                $table->dropColumn('id');
            });

            Schema::table('import_profiles', function (Blueprint $table) {
                $table->uuid('id')->primary()->first();
            });

            // Update foreign keys in dependent tables
            $dependentTables = ['import_jobs', 'import_mappings'];

            foreach ($dependentTables as $tableName) {
                if (Schema::hasTable($tableName)) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->dropForeign(['profile_id']);
                        $table->dropColumn('profile_id');
                    });

                    Schema::table($tableName, function (Blueprint $table) {
                        $table->uuid('profile_id');
                        $table->foreign('profile_id')->references('id')->on('import_profiles')->onDelete('cascade');
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible due to the UUID conversion
        // In a real-world scenario, you would need to backup data before running this
        throw new Exception('This migration cannot be reversed. Please restore from backup if needed.');
    }
};
