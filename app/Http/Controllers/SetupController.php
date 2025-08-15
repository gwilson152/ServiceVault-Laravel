<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Account;
use App\Models\RoleTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    /**
     * Display the initial setup form.
     */
    public function index(): Response
    {
        // The ProtectSetup middleware handles access control
        // If we reach here, either setup is incomplete or user is Super Admin
        return Inertia::render('Setup/Index');
    }

    /**
     * Process the initial setup.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the setup data
        $validator = Validator::make($request->all(), [
            // Company Information
            'company_name' => 'required|string|max:255',
            'company_email' => 'required|email|max:255',
            'company_website' => 'nullable|url|max:255',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:50',
            
            // System Configuration
            'timezone' => 'required|string|max:100',
            'currency' => 'required|string|size:3',
            'date_format' => 'required|string|max:50',
            'time_format' => 'required|string|max:20',
            'language' => 'required|string|max:10',
            
            // Features & Limits
            'enable_real_time' => 'boolean',
            'enable_notifications' => 'boolean',
            'max_account_depth' => 'required|integer|min:1|max:20',
            'timer_sync_interval' => 'required|integer|min:1|max:60',
            'permission_cache_ttl' => 'required|integer|min:60|max:3600',
            
            // User Limits (optional for now - licensing will be implemented later)
            'max_users' => 'required|integer|min:1|max:10000',

            // Admin User Information
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Create the main company account as internal type
        $account = Account::create([
            'name' => $request->company_name,
            'company_name' => $request->company_name,
            'account_type' => 'internal',
            'description' => 'Primary company account (Service Provider)',
            'email' => $request->company_email,
            'website' => $request->company_website,
            'address' => $request->company_address,
            'phone' => $request->company_phone,
            'settings' => [
                'timezone' => $request->timezone,
                'currency' => $request->currency,
                'date_format' => $request->date_format,
                'time_format' => $request->time_format,
                'language' => $request->language,
            ],
            'is_active' => true,
        ]);

        // Store system configuration
        $this->storeSystemConfiguration($request);

        // Store basic license placeholder (licensing will be implemented later)
        $this->storeLicensePlaceholder($request);

        // Ensure role templates are seeded
        try {
            (new \Database\Seeders\RoleTemplateSeeder())->run();
        } catch (\Exception $e) {
            \Log::error('RoleTemplateSeeder failed during setup: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Failed to seed role templates: ' . $e->getMessage()]);
        }
        
        // Seed essential ticket configuration data
        try {
            (new \Database\Seeders\TicketStatusSeeder())->run();
            (new \Database\Seeders\TicketCategorySeeder())->run();
            (new \Database\Seeders\TicketPrioritySeeder())->run();
        } catch (\Exception $e) {
            \Log::error('Ticket configuration seeders failed during setup: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Failed to seed ticket configuration: ' . $e->getMessage()]);
        }
        
        // Seed billing configuration data
        try {
            (new \Database\Seeders\BillingRateSeeder())->run();
            (new \Database\Seeders\AddonTemplateSeeder())->run();
        } catch (\Exception $e) {
            \Log::error('Billing seeders failed during setup: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Failed to seed billing configuration: ' . $e->getMessage()]);
        }

        // Get the super admin role template
        $superAdminTemplate = RoleTemplate::where('name', 'Super Admin')->first();
        
        if (!$superAdminTemplate) {
            return back()->withErrors(['general' => 'Super Admin role template not found. Please contact support.']);
        }

        // Create admin user with direct account and role template assignment
        $adminUser = User::create([
            'name' => $request->admin_name,
            'email' => $request->admin_email,
            'password' => Hash::make($request->admin_password),
            'email_verified_at' => now(),
            'account_id' => $account->id,
            'user_type' => 'agent', // Admin should be an agent to access timer functionality
            'role_template_id' => $superAdminTemplate->id,
        ]);

        // Mark setup as complete
        \App\Models\Setting::create([
            'key' => 'system.setup_complete',
            'value' => true,
            'type' => 'system',
        ]);

        // Clear the setup status cache
        Cache::forget('system_setup_status');

        // Redirect to login page after successful setup with cache-busting headers
        return redirect()->route('login')
            ->with('success', 'Service Vault has been successfully set up! Please log in with your administrator credentials.')
            ->withHeaders([
                'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => 'Thu, 01 Jan 1970 00:00:00 GMT'
            ]);
    }


    /**
     * Store system configuration settings.
     */
    private function storeSystemConfiguration(Request $request): void
    {
        // Update application timezone
        config(['app.timezone' => $request->timezone]);
        
        // Store system settings in the settings table
        $systemSettings = [
            'system.timezone' => $request->timezone,
            'system.currency' => $request->currency,
            'system.date_format' => $request->date_format,
            'system.time_format' => $request->time_format,
            'system.language' => $request->language,
            'system.enable_real_time' => $request->boolean('enable_real_time', true),
            'system.enable_notifications' => $request->boolean('enable_notifications', true),
            'system.max_account_depth' => $request->max_account_depth,
            'system.timer_sync_interval' => $request->timer_sync_interval,
            'system.permission_cache_ttl' => $request->permission_cache_ttl,
        ];

        foreach ($systemSettings as $key => $value) {
            \App\Models\Setting::firstOrCreate(
                ['key' => $key],
                [
                    'key' => $key,
                    'value' => $value,
                    'type' => 'system',
                ]
            );
        }
    }

    /**
     * Store basic license placeholder (licensing will be implemented later).
     */
    private function storeLicensePlaceholder(Request $request): void
    {
        // Store minimal license information - licensing system will be implemented later
        $licenseSettings = [
            'license.max_users' => $request->max_users,
            'license.status' => 'unlicensed_development',
            'license.created_at' => now()->toISOString(),
        ];

        foreach ($licenseSettings as $key => $value) {
            \App\Models\Setting::firstOrCreate(
                ['key' => $key],
                [
                    'key' => $key,
                    'value' => $value,
                    'type' => 'license',
                ]
            );
        }
    }

    /**
     * Check if the system is already set up.
     */
    private function isSystemSetup(): bool
    {
        return User::count() > 0 
            && Account::count() > 0 
            && RoleTemplate::where('is_system_role', true)->count() > 0
            && \App\Models\Setting::where('key', 'license.status')->exists();
    }
}
