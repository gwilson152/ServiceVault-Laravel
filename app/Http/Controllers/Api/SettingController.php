<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\TicketStatus;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\BillingRate;
use App\Models\AddonTemplate;
use App\Models\Account;
use App\Models\RoleTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

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
            'max_account_depth' => 'sometimes|integer|min:1|max:20',
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
                    if ($field === 'company_name') $accountField = 'name';
                    $companyUpdates[$accountField] = $validated[$field];
                    unset($validated[$field]); // Remove from system settings
                }
            }
            if (!empty($companyUpdates)) {
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
                'message' => 'SMTP test failed: ' . $e->getMessage(),
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
            $connectionString = '{' . $host . ':' . $port;
            if ($encryption) {
                $connectionString .= '/' . $encryption;
            }
            $connectionString .= '}' . $folder;

            // Attempt IMAP connection
            $connection = imap_open($connectionString, $username, $password);

            if (!$connection) {
                throw new \Exception('Failed to connect: ' . imap_last_error());
            }

            $status = imap_status($connection, $connectionString, SA_ALL);
            imap_close($connection);

            return response()->json([
                'success' => true,
                'message' => 'IMAP connection successful!',
                'details' => [
                    'total_messages' => $status->messages ?? 0,
                    'unread_messages' => $status->unseen ?? 0,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'IMAP test failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get ticket configuration (statuses and categories)
     */
    public function getTicketConfig(): JsonResponse
    {
        // Allow access to users with appropriate permissions
        if (!auth()->user()->hasAnyPermission(['system.configure', 'tickets.admin', 'admin.read'])) {
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
            \Log::error('Error loading ticket config: ' . $e->getMessage());
            
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
        if (!auth()->user()->hasAnyPermission(['system.configure', 'billing.manage', 'admin.read'])) {
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
            \Log::error('Error loading billing config: ' . $e->getMessage());
            
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
        $this->authorize('system.configure');

        $settings = Setting::getByType('timer');

        // Provide defaults if not set
        $defaults = [
            'default_auto_stop' => false,
            'allow_concurrent_timers' => true,
            'sync_interval_seconds' => 5,
            'auto_commit_on_stop' => false,
            'require_description' => true,
            'default_billable' => true,
        ];

        return response()->json([
            'data' => array_merge($defaults, $settings),
        ]);
    }

    /**
     * Update timer settings
     */
    public function updateTimerSettings(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $validator = Validator::make($request->all(), [
            'default_auto_stop' => 'sometimes|boolean',
            'allow_concurrent_timers' => 'sometimes|boolean',
            'sync_interval_seconds' => 'sometimes|integer|min:1|max:60',
            'auto_commit_on_stop' => 'sometimes|boolean',
            'require_description' => 'sometimes|boolean',
            'default_billable' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($validator->validated() as $key => $value) {
            Setting::setValue("timer.{$key}", $value, 'timer');
        }

        return response()->json([
            'message' => 'Timer settings updated successfully',
        ]);
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
     * Update workflow transitions
     */
    public function updateWorkflowTransitions(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $validated = $request->validate([
            'workflow_transitions' => 'required|array'
        ]);

        // Store workflow transitions in settings
        Setting::setValue('tickets.workflow_transitions', $validated['workflow_transitions'], 'tickets');

        return response()->json([
            'success' => true,
            'message' => 'Workflow transitions updated successfully'
        ]);
    }
}