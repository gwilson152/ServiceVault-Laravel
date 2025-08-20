<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AddonTemplate;
use App\Models\BillingRate;
use App\Models\RoleTemplate;
use App\Models\Setting;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * Get all system settings organized by category
     */
    public function index(): JsonResponse
    {
        $this->authorize('system.configure');

        // Get system settings with system.* prefix
        $systemSettings = Setting::where('key', 'like', 'system.%')->pluck('value', 'key');
        $systemData = [];
        foreach ($systemSettings as $key => $value) {
            // Remove 'system.' prefix from key
            $shortKey = str_replace('system.', '', $key);
            $systemData[$shortKey] = $value;
        }

        // Get company information from the internal account
        $companyAccount = Account::where('account_type', 'internal')->first();
        if ($companyAccount) {
            $systemData['company_name'] = $companyAccount->name;
            $systemData['company_email'] = $companyAccount->email;
            $systemData['company_website'] = $companyAccount->website;
            $systemData['company_address'] = $companyAccount->address;
            $systemData['company_phone'] = $companyAccount->phone;
        }

        // Get email settings with email.* prefix
        $emailSettings = Setting::where('key', 'like', 'email.%')->pluck('value', 'key');
        $emailData = [];
        foreach ($emailSettings as $key => $value) {
            // Remove 'email.' prefix from key
            $shortKey = str_replace('email.', '', $key);
            $emailData[$shortKey] = $value;
        }

        $settings = [
            'system' => $systemData,
            'email' => $emailData,
            'timer' => Setting::getByType('timer'),
            'ticket' => Setting::getByType('ticket'),
            'billing' => Setting::getByType('billing'),
        ];

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Update system settings
     */
    public function updateSystemSettings(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $validator = Validator::make($request->all(), [
            // Company Information
            'company_name' => 'sometimes|string|max:255',
            'company_email' => 'sometimes|email|max:255',
            'company_website' => 'sometimes|nullable|url|max:255',
            'company_address' => 'sometimes|nullable|string|max:500',
            'company_phone' => 'sometimes|nullable|string|max:50',

            // System Configuration
            'timezone' => 'sometimes|string|max:100',
            'currency' => 'sometimes|string|size:3',
            'date_format' => 'sometimes|string|max:50',
            'time_format' => 'sometimes|string|max:20',
            'language' => 'sometimes|string|max:10',

            // Features & Limits
            'enable_real_time' => 'sometimes|boolean',
            'enable_notifications' => 'sometimes|boolean',
            'timer_sync_interval' => 'sometimes|integer|min:1|max:60',
            'permission_cache_ttl' => 'sometimes|integer|min:60|max:3600',
            'max_users' => 'sometimes|integer|min:1|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Update company information in the Account record
        $companyFields = ['company_name', 'company_email', 'company_website', 'company_address', 'company_phone'];
        $companyAccount = Account::where('account_type', 'internal')->first();

        if ($companyAccount) {
            $companyUpdates = [];
            foreach ($companyFields as $field) {
                if (isset($validated[$field])) {
                    $accountField = str_replace('company_', '', $field);
                    if ($field === 'company_name') {
                        $accountField = 'name';
                    }
                    $companyUpdates[$accountField] = $validated[$field];
                    unset($validated[$field]); // Remove from system settings
                }
            }
            if (! empty($companyUpdates)) {
                $companyAccount->update($companyUpdates);
            }
        }

        // Update system settings in the Settings table
        foreach ($validated as $key => $value) {
            Setting::setValue("system.{$key}", $value, 'system');
        }

        return response()->json([
            'message' => 'System settings updated successfully',
        ]);
    }

    /**
     * Update email settings (no validation - allow incomplete saves)
     */
    public function updateEmailSettings(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $emailSettings = [
            // Outbound Email
            'smtp_host' => $request->input('smtp_host'),
            'smtp_port' => $request->input('smtp_port'),
            'smtp_username' => $request->input('smtp_username'),
            'smtp_password' => $request->input('smtp_password'),
            'smtp_encryption' => $request->input('smtp_encryption'),
            'from_address' => $request->input('from_address'),
            'from_name' => $request->input('from_name'),
            'reply_to_address' => $request->input('reply_to_address'),

            // Inbound Email
            'imap_host' => $request->input('imap_host'),
            'imap_port' => $request->input('imap_port'),
            'imap_username' => $request->input('imap_username'),
            'imap_password' => $request->input('imap_password'),
            'imap_encryption' => $request->input('imap_encryption'),
            'imap_folder' => $request->input('imap_folder', 'INBOX'),

            // Email Processing
            'enable_email_to_ticket' => $request->boolean('enable_email_to_ticket', false),
            'auto_create_users' => $request->boolean('auto_create_users', false),
            'default_role_for_new_users' => $request->input('default_role_for_new_users'),
            'require_approval_for_new_users' => $request->boolean('require_approval_for_new_users', true),
        ];

        foreach ($emailSettings as $key => $value) {
            if ($value !== null) {
                Setting::setValue("email.{$key}", $value, 'email');
            }
        }

        return response()->json([
            'message' => 'Email settings saved successfully',
        ]);
    }

    /**
     * Test SMTP email configuration
     */
    public function testSmtp(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $validator = Validator::make($request->all(), [
            'test_email' => 'required|email',
            'smtp_host' => 'required|string',
            'smtp_port' => 'required|integer',
            'smtp_username' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|in:tls,ssl,',
            'from_address' => 'required|email',
            'from_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid configuration',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Temporarily configure mail settings
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $request->smtp_host);
            Config::set('mail.mailers.smtp.port', $request->smtp_port);
            Config::set('mail.mailers.smtp.username', $request->smtp_username);
            Config::set('mail.mailers.smtp.password', $request->smtp_password);
            Config::set('mail.mailers.smtp.encryption', $request->smtp_encryption ?: null);
            Config::set('mail.from.address', $request->from_address);
            Config::set('mail.from.name', $request->from_name ?: 'Service Vault');

            // Configure authentication method based on whether credentials are provided
            if (empty($request->smtp_username) && empty($request->smtp_password)) {
                Config::set('mail.mailers.smtp.auth_mode', null);
            }

            Mail::raw('This is a test email from Service Vault. SMTP configuration is working correctly.', function ($message) use ($request) {
                $message->to($request->test_email)
                    ->subject('Service Vault - SMTP Configuration Test');
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully! Check your inbox.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'SMTP test failed: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Test IMAP connection
     */
    public function testImap(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $validator = Validator::make($request->all(), [
            'imap_host' => 'required|string',
            'imap_port' => 'required|integer',
            'imap_username' => 'required|string',
            'imap_password' => 'required|string',
            'imap_encryption' => 'nullable|in:tls,ssl,',
            'imap_folder' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid configuration',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $host = $request->imap_host;
            $port = $request->imap_port;
            $encryption = $request->imap_encryption;
            $username = $request->imap_username;
            $password = $request->imap_password;
            $folder = $request->input('imap_folder', 'INBOX');

            // Build connection string
            $connectionString = '{'.$host.':'.$port;
            if ($encryption) {
                $connectionString .= '/'.$encryption;
            }
            $connectionString .= '}'.$folder;

            // Attempt IMAP connection
            $connection = imap_open($connectionString, $username, $password);

            if (! $connection) {
                throw new \Exception('Failed to connect: '.imap_last_error());
            }

            $status = imap_status($connection, $connectionString, SA_ALL);
            imap_close($connection);

            return response()->json([
                'success' => true,
                'message' => 'IMAP connection successful!',
                'details' => [
                    'total_messages' => $status->messages ?? 0,
                    'unread_messages' => $status->unseen ?? 0,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'IMAP test failed: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get ticket configuration (statuses and categories)
     */
    public function getTicketConfig(): JsonResponse
    {
        // Allow access to users with ticket viewing permissions or admin access
        if (! auth()->user()->hasAnyPermission([
            'system.configure',
            'tickets.admin',
            'admin.read',
            'tickets.view.own',
            'tickets.view.account',
            'tickets.view.all',
            'portal.access',
        ])) {
            abort(403, 'Unauthorized');
        }

        try {
            return response()->json([
                'data' => [
                    'statuses' => TicketStatus::active()->ordered()->get(),
                    'categories' => TicketCategory::active()->ordered()->get(),
                    'priorities' => TicketPriority::active()->ordered()->get(),
                    'workflow_transitions' => TicketStatus::getWorkflowTransitions(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading ticket config: '.$e->getMessage());

            return response()->json([
                'data' => [
                    'statuses' => [],
                    'categories' => [],
                    'priorities' => [],
                    'workflow_transitions' => [],
                ],
            ]);
        }
    }

    /**
     * Get billing configuration (rates and addon templates)
     */
    public function getBillingConfig(): JsonResponse
    {
        // Allow access to users with either system.configure or billing.manage permissions
        if (! auth()->user()->hasAnyPermission(['system.configure', 'billing.manage', 'admin.read'])) {
            abort(403, 'Unauthorized');
        }

        try {
            return response()->json([
                'data' => [
                    'billing_rates' => BillingRate::with(['account', 'user'])->where('is_active', true)->get(),
                    'addon_templates' => AddonTemplate::active()->ordered()->get(),
                    'addon_categories' => AddonTemplate::getCategories(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error loading billing config: '.$e->getMessage());

            // Return empty data structure to prevent frontend errors
            return response()->json([
                'data' => [
                    'billing_rates' => [],
                    'addon_templates' => [],
                    'addon_categories' => AddonTemplate::getCategories(),
                ],
            ]);
        }
    }

    /**
     * Get timer settings
     */
    public function getTimerSettings(): JsonResponse
    {
        // Allow access to users with timer permissions or general read access
        if (! auth()->user()->hasAnyPermission(['timers.read', 'timers.write', 'admin.read', 'system.configure'])) {
            abort(403, 'Unauthorized');
        }

        try {
            // Get timer settings with timer.* prefix
            $timerSettings = Setting::where('key', 'like', 'timer.%')->pluck('value', 'key');
            $timerData = [];

            foreach ($timerSettings as $key => $value) {
                // Remove 'timer.' prefix from key
                $shortKey = str_replace('timer.', '', $key);
                $timerData[$shortKey] = $value;
            }

            // Apply comprehensive defaults for missing settings
            $defaults = [
                'default_auto_stop' => false,
                'allow_concurrent_timers' => true,
                'auto_commit_on_stop' => false,
                'require_description' => true,
                'default_billable' => true,
                'sync_interval_seconds' => 5,
                'min_timer_duration_minutes' => 0,
                'max_timer_duration_hours' => 8,
                'auto_stop_long_timers' => false,
                'time_display_format' => 'hms',
                'show_timer_overlay' => true,
                'play_timer_sounds' => false,
                'allow_manual_time_override' => true,
            ];

            foreach ($defaults as $key => $defaultValue) {
                if (! isset($timerData[$key])) {
                    $timerData[$key] = $defaultValue;
                }

                // Convert string boolean values to actual booleans
                if (in_array($key, ['default_auto_stop', 'allow_concurrent_timers', 'auto_commit_on_stop', 'require_description', 'default_billable', 'auto_stop_long_timers', 'show_timer_overlay', 'play_timer_sounds', 'allow_manual_time_override'])) {
                    $timerData[$key] = filter_var($timerData[$key], FILTER_VALIDATE_BOOLEAN);
                }

                // Convert string numeric values to numbers
                if (in_array($key, ['sync_interval_seconds', 'min_timer_duration_minutes', 'max_timer_duration_hours'])) {
                    $timerData[$key] = (int) $timerData[$key];
                }
            }

            return response()->json([
                'data' => $timerData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load timer settings: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update timer settings
     */
    public function updateTimerSettings(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $rules = [
            'default_auto_stop' => 'boolean',
            'allow_concurrent_timers' => 'boolean',
            'auto_commit_on_stop' => 'boolean',
            'require_description' => 'boolean',
            'default_billable' => 'boolean',
            'sync_interval_seconds' => 'integer|min:1|max:60',
            'min_timer_duration_minutes' => 'integer|min:0|max:1440',
            'max_timer_duration_hours' => 'integer|min:1|max:24',
            'auto_stop_long_timers' => 'boolean',
            'time_display_format' => 'string|in:hms,hm,decimal',
            'show_timer_overlay' => 'boolean',
            'play_timer_sounds' => 'boolean',
            'allow_manual_time_override' => 'boolean',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid timer settings',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Update each setting with timer. prefix
            foreach ($request->all() as $key => $value) {
                if (isset($rules[$key])) {
                    Setting::setValue("timer.{$key}", $value, 'timer');
                }
            }

            // Return updated settings
            return $this->getTimerSettings();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update timer settings: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user management settings
     */
    public function getUserManagementSettings(): JsonResponse
    {
        $this->authorize('system.configure');

        return response()->json([
            'data' => [
                'accounts' => Account::select(['id', 'name', 'account_type'])->get(),
                'role_templates' => RoleTemplate::select(['id', 'name', 'context'])->get(),
                'auto_user_settings' => Setting::getByType('auto_user'),
            ],
        ]);
    }

    /**
     * Update user management settings
     */
    public function updateUserManagementSettings(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $validator = Validator::make($request->all(), [
            'enable_auto_user_creation' => 'sometimes|boolean',
            'default_account_for_new_users' => 'sometimes|nullable|exists:accounts,id',
            'default_role_template_for_new_users' => 'sometimes|nullable|exists:role_templates,id',
            'require_email_verification' => 'sometimes|boolean',
            'require_admin_approval' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($validator->validated() as $key => $value) {
            Setting::setValue("auto_user.{$key}", $value, 'auto_user');
        }

        return response()->json([
            'message' => 'User management settings updated successfully',
        ]);
    }

    /**
     * Get advanced settings
     */
    public function getAdvancedSettings(): JsonResponse
    {
        $this->authorize('system.configure');

        // Get advanced settings with advanced.* prefix
        $advancedSettings = Setting::where('key', 'like', 'advanced.%')->pluck('value', 'key');
        $advancedData = [];
        foreach ($advancedSettings as $key => $value) {
            // Remove 'advanced.' prefix from key
            $shortKey = str_replace('advanced.', '', $key);
            $advancedData[$shortKey] = $value;
        }

        // Set defaults if not present
        $advancedData['show_debug_overlay'] = $advancedData['show_debug_overlay'] ?? false;
        $advancedData['show_permissions_debug_overlay'] = $advancedData['show_permissions_debug_overlay'] ?? false;

        return response()->json([
            'message' => 'Advanced settings retrieved successfully',
            'data' => $advancedData,
        ]);
    }

    /**
     * Update advanced settings
     */
    public function updateAdvancedSettings(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $validator = Validator::make($request->all(), [
            'show_debug_overlay' => 'sometimes|boolean',
            'show_permissions_debug_overlay' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update settings with advanced.* prefix
        foreach ($validator->validated() as $key => $value) {
            Setting::setValue("advanced.{$key}", $value, 'advanced');
        }

        return response()->json([
            'message' => 'Advanced settings updated successfully',
        ]);
    }

    /**
     * Update workflow transitions
     */
    public function updateWorkflowTransitions(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $validated = $request->validate([
            'workflow_transitions' => 'required|array',
        ]);

        // Store workflow transitions in settings
        Setting::setValue('tickets.workflow_transitions', $validated['workflow_transitions'], 'tickets');

        return response()->json([
            'success' => true,
            'message' => 'Workflow transitions updated successfully',
        ]);
    }

    /**
     * Perform nuclear system reset - complete system wipe and reset
     * Requires super admin privileges and password confirmation
     */
    public function nuclearReset(Request $request): JsonResponse
    {
        // First check: Must be authenticated
        if (! Auth::check()) {
            return response()->json([
                'message' => 'Authentication required',
            ], 401);
        }

        $user = Auth::user();

        // Second check: Must be Super Admin
        if (! $user->isSuperAdmin()) {
            Log::warning('Non-super-admin attempted nuclear reset', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'ip' => $request->ip(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'message' => 'Access denied. Only Super Administrators can perform system reset.',
            ], 403);
        }

        // Third check: Password confirmation is required
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Password confirmation is required',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Fourth check: Verify password
        if (! Hash::check($request->password, $user->password)) {
            Log::warning('Nuclear reset attempted with invalid password', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'ip' => $request->ip(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'message' => 'Invalid password. Nuclear reset cancelled.',
            ], 422);
        }

        // Log the nuclear reset attempt
        Log::critical('Nuclear system reset initiated by super admin', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
        ]);

        try {
            // Execute nuclear reset via artisan command for safety and logging
            $exitCode = Artisan::call('system:nuclear-reset', [
                '--user-id' => $user->id,
            ]);

            if ($exitCode === 0) {
                Log::info('Nuclear system reset completed successfully', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'timestamp' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Nuclear reset completed successfully. System has been reset to initial state. You will be redirected to setup.',
                    'redirect_to' => '/setup',
                ]);
            } else {
                throw new \Exception('Nuclear reset command failed with exit code: '.$exitCode);
            }

        } catch (\Exception $e) {
            Log::error('Nuclear system reset failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'timestamp' => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Nuclear reset failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tax configuration settings
     */
    public function getTaxSettings(): JsonResponse
    {
        $this->authorize('system.configure');

        // Get tax settings with tax.* prefix
        $taxSettings = Setting::where('key', 'like', 'tax.%')
                              ->where('type', 'system')
                              ->pluck('value', 'key');
        $taxData = [];
        foreach ($taxSettings as $key => $value) {
            // Remove 'tax.' prefix from key
            $shortKey = str_replace('tax.', '', $key);
            $taxData[$shortKey] = $value;
        }

        // Set defaults if not present
        $taxData['enabled'] = $taxData['enabled'] ?? true;
        $taxData['default_rate'] = $taxData['default_rate'] ?? 0.0;
        $taxData['default_application_mode'] = $taxData['default_application_mode'] ?? 'all_items';

        return response()->json([
            'data' => $taxData,
        ]);
    }

    /**
     * Update tax configuration settings
     */
    public function updateTaxSettings(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $validator = Validator::make($request->all(), [
            'enabled' => 'sometimes|boolean',
            'default_rate' => 'sometimes|numeric|min:0|max:100',
            'default_application_mode' => 'sometimes|string|in:all_items,non_service_items,custom',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update tax settings with tax.* prefix
        foreach ($validator->validated() as $key => $value) {
            Setting::setValue("tax.{$key}", $value, 'system');
        }

        return response()->json([
            'message' => 'Tax settings updated successfully',
        ]);
    }
}
