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
        // Check if account tax columns exist before migration
        if (! Schema::hasColumn('accounts', 'default_tax_rate')) {
            // Columns don't exist, nothing to migrate
            return;
        }

        // Migrate existing account tax settings to settings table
        $accounts = DB::table('accounts')
            ->whereNotNull('default_tax_rate')
            ->orWhereNotNull('default_tax_application_mode')
            ->orWhere('tax_exempt', true)
            ->get();

        foreach ($accounts as $account) {
            // Only migrate non-default values
            if ($account->default_tax_rate !== null && $account->default_tax_rate > 0) {
                DB::table('settings')->insertOrIgnore([
                    'id' => DB::raw('gen_random_uuid()'),
                    'key' => 'tax.default_rate',
                    'value' => json_encode($account->default_tax_rate),
                    'type' => 'account',
                    'account_id' => $account->id,
                    'user_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($account->default_tax_application_mode !== null && $account->default_tax_application_mode !== 'all_items') {
                DB::table('settings')->insertOrIgnore([
                    'id' => DB::raw('gen_random_uuid()'),
                    'key' => 'tax.default_application_mode',
                    'value' => json_encode($account->default_tax_application_mode),
                    'type' => 'account',
                    'account_id' => $account->id,
                    'user_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($account->tax_exempt) {
                DB::table('settings')->insertOrIgnore([
                    'id' => DB::raw('gen_random_uuid()'),
                    'key' => 'tax.exempt',
                    'value' => json_encode(true),
                    'type' => 'account',
                    'account_id' => $account->id,
                    'user_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Remove the tax columns from accounts table now that data is migrated
        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'default_tax_rate')) {
                $table->dropColumn('default_tax_rate');
            }
            if (Schema::hasColumn('accounts', 'default_tax_application_mode')) {
                $table->dropColumn('default_tax_application_mode');
            }
            if (Schema::hasColumn('accounts', 'tax_exempt')) {
                $table->dropColumn('tax_exempt');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the columns to accounts table
        Schema::table('accounts', function (Blueprint $table) {
            $table->decimal('default_tax_rate', 5, 4)->default(0)->after('billing_country');
            $table->enum('default_tax_application_mode', ['all_items', 'non_service_items', 'custom'])
                ->default('all_items')->after('default_tax_rate');
            $table->boolean('tax_exempt')->default(false)->after('default_tax_application_mode');
        });

        // Migrate settings back to account columns
        $taxSettings = DB::table('settings')
            ->where('type', 'account')
            ->whereIn('key', ['tax.default_rate', 'tax.default_application_mode', 'tax.exempt'])
            ->get();

        $accountUpdates = [];
        foreach ($taxSettings as $setting) {
            if (! isset($accountUpdates[$setting->account_id])) {
                $accountUpdates[$setting->account_id] = [];
            }

            switch ($setting->key) {
                case 'tax.default_rate':
                    $accountUpdates[$setting->account_id]['default_tax_rate'] = json_decode($setting->value);
                    break;
                case 'tax.default_application_mode':
                    $accountUpdates[$setting->account_id]['default_tax_application_mode'] = json_decode($setting->value);
                    break;
                case 'tax.exempt':
                    $accountUpdates[$setting->account_id]['tax_exempt'] = json_decode($setting->value);
                    break;
            }
        }

        // Apply updates to accounts
        foreach ($accountUpdates as $accountId => $updates) {
            DB::table('accounts')->where('id', $accountId)->update($updates);
        }

        // Remove the migrated settings
        DB::table('settings')
            ->where('type', 'account')
            ->whereIn('key', ['tax.default_rate', 'tax.default_application_mode', 'tax.exempt'])
            ->delete();
    }
};
