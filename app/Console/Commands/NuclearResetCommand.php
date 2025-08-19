<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NuclearResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:nuclear-reset {--user-id= : ID of the user performing the reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a complete system reset (migrate:fresh --seed and clear setup completion)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');

        $this->warn('🚨 NUCLEAR SYSTEM RESET INITIATED 🚨');
        $this->info('This will completely wipe all data and reset the system to initial state.');

        Log::warning('Nuclear system reset initiated', [
            'user_id' => $userId,
            'timestamp' => now(),
            'ip' => request()->ip() ?? 'console',
        ]);

        try {
            // Step 1: Clear all caches
            $this->info('Step 1/4: Clearing application cache...');
            Cache::flush();
            Artisan::call('cache:clear');
            $this->info('✅ Cache cleared');

            // Step 2: Run migrate:fresh --seed
            $this->info('Step 2/4: Resetting database (migrate:fresh --seed)...');
            Artisan::call('migrate:fresh', ['--seed' => true]);
            $this->info('✅ Database reset and seeded');

            // Step 3: Remove setup completion flag
            $this->info('Step 3/4: Clearing setup completion flag...');
            Setting::where('key', 'system.setup_complete')->delete();
            $this->info('✅ Setup completion flag cleared');

            // Step 4: Clear cache again to ensure clean state
            $this->info('Step 4/4: Final cache clear...');
            Cache::flush();
            Artisan::call('cache:clear');
            $this->info('✅ Final cache clear completed');

            $this->warn('🚨 NUCLEAR RESET COMPLETED SUCCESSFULLY 🚨');
            $this->info('System has been reset to initial state.');
            $this->info('The application will now redirect to /setup for reconfiguration.');

            Log::info('Nuclear system reset completed successfully', [
                'user_id' => $userId,
                'timestamp' => now(),
                'ip' => request()->ip() ?? 'console',
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Nuclear reset failed: '.$e->getMessage());

            Log::error('Nuclear system reset failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now(),
                'ip' => request()->ip() ?? 'console',
            ]);

            return Command::FAILURE;
        }
    }
}
