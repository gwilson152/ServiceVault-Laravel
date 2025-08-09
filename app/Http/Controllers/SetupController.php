<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Account;
use App\Models\Role;
use App\Models\RoleTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    /**
     * Display the initial setup form.
     */
    public function index(): Response
    {
        // Check if setup is already complete
        if ($this->isSystemSetup()) {
            return Inertia::render('Setup/AlreadyComplete');
        }

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

        // Create the main company account
        $account = Account::create([
            'name' => $request->company_name,
            'slug' => Str::slug($request->company_name),
            'description' => 'Primary company account',
            'settings' => [
                'email' => $request->company_email,
                'website' => $request->company_website,
                'address' => $request->company_address,
                'phone' => $request->company_phone,
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

        // Create system role templates
        $this->createSystemRoleTemplates();

        // Get the super admin role template
        $superAdminTemplate = RoleTemplate::where('name', 'Super Administrator')->first();

        // Create admin user
        $adminUser = User::create([
            'name' => $request->admin_name,
            'email' => $request->admin_email,
            'password' => Hash::make($request->admin_password),
            'email_verified_at' => now(),
        ]);

        // Create and assign super admin role to the user
        $adminRole = Role::create([
            'account_id' => $account->id,
            'role_template_id' => $superAdminTemplate->id,
        ]);

        $adminUser->roles()->attach($adminRole->id);
        $account->users()->attach($adminUser->id);

        // Clear the setup status cache
        Cache::forget('system_setup_status');

        // Log in the admin user
        Auth::login($adminUser);

        return redirect()->route('dashboard')->with('success', 'Service Vault has been successfully set up!');
    }

    /**
     * Create system role templates.
     */
    private function createSystemRoleTemplates(): void
    {
        $roleTemplates = [
            [
                'name' => 'Super Administrator',
                'permissions' => [
                    'system.manage', 'accounts.create', 'accounts.manage', 'users.manage',
                    'role_templates.manage', 'timers.manage', 'billing.manage', 'settings.manage'
                ],
                'is_system_role' => true,
                'is_default' => false,
                'description' => 'Full system access with role template management capabilities',
            ],
            [
                'name' => 'System Administrator',
                'permissions' => [
                    'accounts.create', 'accounts.manage', 'users.manage',
                    'timers.manage', 'billing.manage', 'settings.manage'
                ],
                'is_system_role' => true,
                'is_default' => false,
                'description' => 'System administration without role template management',
            ],
            [
                'name' => 'Account Manager',
                'permissions' => [
                    'account.manage', 'users.assign', 'projects.manage', 'billing.view'
                ],
                'is_system_role' => false,
                'is_default' => false,
                'description' => 'Manage specific accounts and their users',
            ],
            [
                'name' => 'Team Lead',
                'permissions' => [
                    'team.manage', 'projects.manage', 'time_entries.approve', 'reports.view'
                ],
                'is_system_role' => false,
                'is_default' => false,
                'description' => 'Lead teams and approve time entries',
            ],
            [
                'name' => 'Employee',
                'permissions' => [
                    'timers.create', 'timers.manage', 'time_entries.create', 'projects.view'
                ],
                'is_system_role' => false,
                'is_default' => true,
                'description' => 'Standard employee with time tracking capabilities',
            ],
            [
                'name' => 'Customer',
                'permissions' => [
                    'portal.access', 'tickets.view', 'invoices.view'
                ],
                'is_system_role' => false,
                'is_default' => false,
                'description' => 'Customer portal access with limited visibility',
            ],
        ];

        foreach ($roleTemplates as $template) {
            RoleTemplate::create($template);
        }
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
            \App\Models\Setting::create([
                'key' => $key,
                'value' => $value,
                'type' => 'system',
            ]);
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
            \App\Models\Setting::create([
                'key' => $key,
                'value' => $value,
                'type' => 'license',
            ]);
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
