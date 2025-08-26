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
use Illuminate\Support\Facades\Http;
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
     * Get email settings
     */
    public function getEmailSettings(): JsonResponse
    {
        $this->authorize('system.configure');

        // Get email settings with email.* prefix
        $emailSettings = Setting::where('key', 'like', 'email.%')->pluck('value', 'key');
        $emailData = [];
        foreach ($emailSettings as $key => $value) {
            // Remove 'email.' prefix from key
            $shortKey = str_replace('email.', '', $key);
            $emailData[$shortKey] = $value;
        }

        return response()->json([
            'data' => $emailData,
        ]);
    }

    /**
     * Update email settings (no validation - allow incomplete saves)
     */
    public function updateEmailSettings(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $emailSettings = [
            // Email Provider Type
            'email_provider' => $request->input('email_provider', 'imap'), // 'imap' or 'm365'

            // Outbound Email
            'smtp_host' => $request->input('smtp_host'),
            'smtp_port' => $request->input('smtp_port'),
            'smtp_username' => $request->input('smtp_username'),
            'smtp_password' => $request->input('smtp_password'),
            'smtp_encryption' => $request->input('smtp_encryption'),
            'from_address' => $request->input('from_address'),
            'from_name' => $request->input('from_name'),
            'reply_to_address' => $request->input('reply_to_address'),

            // Inbound Email - IMAP
            'imap_host' => $request->input('imap_host'),
            'imap_port' => $request->input('imap_port'),
            'imap_username' => $request->input('imap_username'),
            'imap_password' => $request->input('imap_password'),
            'imap_encryption' => $request->input('imap_encryption'),
            'imap_folder' => $request->input('imap_folder', 'INBOX'),

            // Inbound Email - Microsoft 365 Graph API
            'm365_tenant_id' => $request->input('m365_tenant_id'),
            'm365_client_id' => $request->input('m365_client_id'),
            'm365_client_secret' => $request->input('m365_client_secret'),
            'm365_mailbox' => $request->input('m365_mailbox'),
            'm365_folder_id' => $request->input('m365_folder_id', 'inbox'),
            'm365_folder_name' => $request->input('m365_folder_name', 'Inbox'),

            // Email Processing Actions
            'post_processing_action' => $request->input('post_processing_action', 'mark_read'), // 'mark_read', 'move_folder', 'delete'
            'move_to_folder_id' => $request->input('move_to_folder_id'),
            'move_to_folder_name' => $request->input('move_to_folder_name'),

            // Email Processing
            'enable_email_to_ticket' => $request->boolean('enable_email_to_ticket', false),
            'auto_create_users' => $request->boolean('auto_create_users', false),
            'default_role_for_new_users' => $request->input('default_role_for_new_users'),
            'require_approval_for_new_users' => $request->boolean('require_approval_for_new_users', true),
            
            // Timestamp Processing
            'timestamp_source' => $request->input('timestamp_source', 'service_vault'), // 'service_vault' or 'original'
            'timestamp_timezone' => $request->input('timestamp_timezone', 'preserve'), // 'preserve', 'convert_local', or 'convert_utc'
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
     * Test Microsoft 365 Graph API connection
     */
    public function testM365(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $validator = Validator::make($request->all(), [
            'm365_tenant_id' => 'required|string',
            'm365_client_id' => 'required|string', 
            'm365_client_secret' => 'required|string',
            'm365_mailbox' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid configuration',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->m365_tenant_id;
            $clientId = $request->m365_client_id;
            $clientSecret = $request->m365_client_secret;
            $mailbox = $request->m365_mailbox;

            // Get access token using client credentials flow
            $tokenResponse = $this->getM365AccessToken($tenantId, $clientId, $clientSecret);

            if (!$tokenResponse['success']) {
                throw new \Exception($tokenResponse['message']);
            }

            $accessToken = $tokenResponse['token'];

            // Test mailbox access by getting basic mailbox info
            $response = $this->makeM365Request(
                "https://graph.microsoft.com/v1.0/users/{$mailbox}/mailFolders/inbox",
                $accessToken
            );

            if (!$response['success']) {
                throw new \Exception($response['message']);
            }

            $folderInfo = $response['data'];

            return response()->json([
                'success' => true,
                'message' => 'Microsoft 365 Graph API connection successful!',
                'details' => [
                    'mailbox' => $mailbox,
                    'inbox_messages' => $folderInfo['totalItemCount'] ?? 0,
                    'unread_messages' => $folderInfo['unreadItemCount'] ?? 0,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'M365 Graph API test failed: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get Microsoft 365 mailbox folders
     */
    public function getM365Folders(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $validator = Validator::make($request->all(), [
            'm365_tenant_id' => 'required|string',
            'm365_client_id' => 'required|string',
            'm365_client_secret' => 'required|string',
            'm365_mailbox' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid configuration',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->m365_tenant_id;
            $clientId = $request->m365_client_id;
            $clientSecret = $request->m365_client_secret;
            $mailbox = $request->m365_mailbox;

            // Get access token
            $tokenResponse = $this->getM365AccessToken($tenantId, $clientId, $clientSecret);

            if (!$tokenResponse['success']) {
                throw new \Exception($tokenResponse['message']);
            }

            $accessToken = $tokenResponse['token'];

            // Get all root folders with pagination support and cache-busting
            \Log::info('Starting to get M365 folders for mailbox: ' . $mailbox);
            
            $folders = [];
            $nextUrl = "https://graph.microsoft.com/v1.0/users/{$mailbox}/mailFolders?\$select=id,displayName,parentFolderId,totalItemCount,unreadItemCount&\$top=999&_=" . time();
            
            // Handle pagination to get all folders
            while ($nextUrl) {
                $response = $this->makeM365Request($nextUrl, $accessToken);

                if (!$response['success']) {
                    \Log::error('Failed to get root folders: ' . $response['message']);
                    throw new \Exception($response['message']);
                }

                $pageData = $response['data'];
                $pageFolders = $pageData['value'] ?? [];
                $folders = array_merge($folders, $pageFolders);
                
                // Check for next page
                $nextUrl = $pageData['@odata.nextLink'] ?? null;
                \Log::info('Retrieved page with ' . count($pageFolders) . ' folders, total: ' . count($folders) . ', has next page: ' . ($nextUrl ? 'yes' : 'no'));
            }
            \Log::info('Root folders retrieved: ' . count($folders) . ' folders');
            
            if (empty($folders)) {
                \Log::warning('No folders found for mailbox: ' . $mailbox);
                return response()->json([
                    'success' => false,
                    'message' => 'No folders found in mailbox. Please check permissions.',
                ]);
            }

            // Try to get child folders, but don't fail if it doesn't work
            $allFolders = $folders;
            \Log::info('Attempting to get child folders for ' . count($folders) . ' root folders');
            
            $childFoldersFound = 0;
            foreach ($folders as $rootFolder) {
                try {
                    // Get child folders with pagination and cache-busting
                    $childFolders = [];
                    $childNextUrl = "https://graph.microsoft.com/v1.0/users/{$mailbox}/mailFolders/{$rootFolder['id']}/childFolders?\$select=id,displayName,parentFolderId,totalItemCount,unreadItemCount&\$top=999&_=" . time();
                    
                    while ($childNextUrl) {
                        $childResponse = $this->makeM365Request($childNextUrl, $accessToken);
                        
                        if ($childResponse['success'] && isset($childResponse['data']['value'])) {
                            $pageChildFolders = $childResponse['data']['value'];
                            $childFolders = array_merge($childFolders, $pageChildFolders);
                            
                            // Check for next page
                            $childNextUrl = $childResponse['data']['@odata.nextLink'] ?? null;
                        } else {
                            break; // Exit the pagination loop on error
                        }
                    }
                    
                    if (count($childFolders) > 0) {
                        \Log::info('Found ' . count($childFolders) . ' child folders for ' . $rootFolder['displayName']);
                        $allFolders = array_merge($allFolders, $childFolders);
                        $childFoldersFound += count($childFolders);
                    } else {
                        \Log::debug('No child folders for ' . $rootFolder['displayName']);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to get child folders for ' . $rootFolder['displayName'] . ': ' . $e->getMessage());
                    // Continue with other folders - don't break the whole process
                }
            }
            
            \Log::info('Total folders found - Root: ' . count($folders) . ', Children: ' . $childFoldersFound . ', Total: ' . count($allFolders));
            
            // Log folder parent relationships for debugging
            $sampleFolders = array_slice($allFolders, 0, 5);
            \Log::info('Sample folder data', [
                'folders' => array_map(function($folder) {
                    return [
                        'id' => substr($folder['id'], 0, 20) . '...',
                        'name' => $folder['displayName'],
                        'parent_id' => $folder['parentFolderId'] ? substr($folder['parentFolderId'], 0, 20) . '...' : null
                    ];
                }, $sampleFolders)
            ]);

            // Format folders with hierarchy
            $formattedFolders = $this->buildSimpleFolderHierarchy($allFolders);
            
            \Log::info('Formatted folders: ' . count($formattedFolders));
            
            // Fallback: if hierarchy building failed, just return flat list
            if (empty($formattedFolders) && !empty($allFolders)) {
                \Log::warning('Hierarchy building failed, falling back to flat list');
                $formattedFolders = [];
                foreach ($allFolders as $folder) {
                    $formattedFolders[] = [
                        'id' => $folder['id'],
                        'name' => $folder['displayName'],
                        'original_name' => $folder['displayName'],
                        'full_path' => $folder['displayName'],
                        'parent_id' => $folder['parentFolderId'] ?? null,
                        'total_count' => $folder['totalItemCount'] ?? 0,
                        'unread_count' => $folder['unreadItemCount'] ?? 0,
                        'level' => 0,
                        'sort_key' => $folder['displayName'],
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'folders' => $formattedFolders,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get M365 folders: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Test email retrieval from M365 folder
     */
    public function testM365EmailRetrieval(Request $request): JsonResponse
    {
        $this->authorize('system.configure');

        $validator = Validator::make($request->all(), [
            'm365_tenant_id' => 'required|string',
            'm365_client_id' => 'required|string',
            'm365_client_secret' => 'required|string',
            'm365_mailbox' => 'required|email',
            'm365_folder_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid configuration',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->m365_tenant_id;
            $clientId = $request->m365_client_id;
            $clientSecret = $request->m365_client_secret;
            $mailbox = $request->m365_mailbox;
            $folderId = $request->m365_folder_id;

            // Get access token
            $tokenResponse = $this->getM365AccessToken($tenantId, $clientId, $clientSecret);

            if (!$tokenResponse['success']) {
                throw new \Exception($tokenResponse['message']);
            }

            $accessToken = $tokenResponse['token'];

            // Get last 5 emails from the specified folder
            \Log::info('Testing email retrieval from M365 folder', [
                'mailbox' => $mailbox,
                'folder_id' => $folderId
            ]);

            $emailsUrl = "https://graph.microsoft.com/v1.0/users/{$mailbox}/mailFolders/{$folderId}/messages";
            $emailsUrl .= "?\$select=id,subject,from,receivedDateTime,bodyPreview,hasAttachments,isRead";
            $emailsUrl .= "&\$orderby=receivedDateTime desc&\$top=5";

            $response = $this->makeM365Request($emailsUrl, $accessToken);

            if (!$response['success']) {
                throw new \Exception($response['message']);
            }

            $emails = $response['data']['value'] ?? [];
            
            // Format emails for display
            $formattedEmails = [];
            foreach ($emails as $email) {
                $fromAddress = $email['from']['emailAddress']['address'] ?? 'Unknown';
                $fromName = $email['from']['emailAddress']['name'] ?? $fromAddress;
                
                $formattedEmails[] = [
                    'id' => $email['id'],
                    'subject' => $email['subject'] ?? '(No Subject)',
                    'from_address' => $fromAddress,
                    'from_name' => $fromName,
                    'received_at' => $email['receivedDateTime'] ?? null,
                    'body_preview' => $email['bodyPreview'] ?? '',
                    'has_attachments' => $email['hasAttachments'] ?? false,
                    'is_read' => $email['isRead'] ?? false,
                    'received_formatted' => isset($email['receivedDateTime']) 
                        ? \Carbon\Carbon::parse($email['receivedDateTime'])->format('M j, Y g:i A')
                        : 'Unknown',
                ];
            }

            \Log::info('Retrieved emails from M365 folder', [
                'folder_id' => $folderId,
                'email_count' => count($formattedEmails)
            ]);

            return response()->json([
                'success' => true,
                'emails' => $formattedEmails,
                'folder_id' => $folderId,
                'total_retrieved' => count($formattedEmails),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to test M365 email retrieval', [
                'error' => $e->getMessage(),
                'mailbox' => $request->m365_mailbox ?? null,
                'folder_id' => $request->m365_folder_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve emails: '.$e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get Microsoft 365 access token using client credentials flow
     */
    private function getM365AccessToken(string $tenantId, string $clientId, string $clientSecret): array
    {
        try {
            $response = \Http::asForm()->post(
                "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
                [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ]
            );

            if ($response->failed()) {
                $error = $response->json('error_description') ?? 'Authentication failed';
                return ['success' => false, 'message' => $error];
            }

            $data = $response->json();
            return [
                'success' => true,
                'token' => $data['access_token'],
                'expires_in' => $data['expires_in'],
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Make authenticated request to Microsoft Graph API
     */
    private function makeM365Request(string $url, string $accessToken): array
    {
        try {
            \Log::info('Making M365 API request', ['url' => $url]);
            
            $response = \Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->timeout(30)->get($url);

            \Log::info('M365 API response status', ['status' => $response->status()]);

            if ($response->failed()) {
                $errorData = $response->json();
                \Log::error('M365 API request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'error' => $errorData
                ]);
                
                $error = $errorData['error']['message'] ?? 'API request failed with status ' . $response->status();
                return ['success' => false, 'message' => $error];
            }

            $data = $response->json();
            \Log::info('M365 API request successful', ['data_keys' => array_keys($data)]);

            return [
                'success' => true,
                'data' => $data,
            ];

        } catch (\Exception $e) {
            \Log::error('M365 API request exception', [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get all M365 folders recursively by making multiple API calls
     */
    private function getAllM365Folders(string $accessToken, string $mailbox): array
    {
        try {
            $allFolders = [];
            
            // First, get all root-level folders
            $response = $this->makeM365Request(
                "https://graph.microsoft.com/v1.0/users/{$mailbox}/mailFolders?\$select=id,displayName,parentFolderId,totalItemCount,unreadItemCount",
                $accessToken
            );

            if (!$response['success']) {
                return $response;
            }

            $rootFolders = $response['data']['value'] ?? [];
            
            \Log::info('M365 Root folders retrieved', ['count' => count($rootFolders), 'folders' => array_column($rootFolders, 'displayName')]);
            
            // Process each root folder and get its children recursively
            foreach ($rootFolders as $folder) {
                $this->collectFolderHierarchy($folder, $allFolders, $accessToken, $mailbox);
            }
            
            \Log::info('All folders collected', ['count' => count($allFolders)]);
            
            // Build the display hierarchy
            $formattedFolders = $this->buildFolderHierarchy($allFolders);
            
            \Log::info('Formatted folders built', ['count' => count($formattedFolders)]);
            
            return [
                'success' => true,
                'folders' => $formattedFolders
            ];
            
        } catch (\Exception $e) {
            \Log::error('Error getting M365 folders', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Recursively collect folder hierarchy by making API calls for child folders
     */
    private function collectFolderHierarchy(array $folder, array &$allFolders, string $accessToken, string $mailbox): void
    {
        // Add current folder to collection
        $allFolders[$folder['id']] = [
            'id' => $folder['id'],
            'name' => $folder['displayName'],
            'parent_id' => $folder['parentFolderId'] ?? null,
            'total_count' => $folder['totalItemCount'] ?? 0,
            'unread_count' => $folder['unreadItemCount'] ?? 0,
            'children' => []
        ];
        
        // Get child folders for this folder
        $childResponse = $this->makeM365Request(
            "https://graph.microsoft.com/v1.0/users/{$mailbox}/mailFolders/{$folder['id']}/childFolders?\$select=id,displayName,parentFolderId,totalItemCount,unreadItemCount",
            $accessToken
        );
        
        if ($childResponse['success'] && isset($childResponse['data']['value'])) {
            $childFolders = $childResponse['data']['value'];
            
            foreach ($childFolders as $childFolder) {
                $allFolders[$folder['id']]['children'][] = $childFolder['id'];
                // Recursively get children of this child
                $this->collectFolderHierarchy($childFolder, $allFolders, $accessToken, $mailbox);
            }
        }
    }

    /**
     * Build formatted folder hierarchy for display
     */
    private function buildFolderHierarchy(array $allFolders): array
    {
        $formattedFolders = [];
        
        \Log::info('Building hierarchy from folders', ['all_folders_count' => count($allFolders)]);
        
        // Find root folders (those without parents)
        $rootFolders = array_filter($allFolders, function($folder) {
            return empty($folder['parent_id']);
        });
        
        \Log::info('Root folders found', ['count' => count($rootFolders), 'names' => array_column($rootFolders, 'name')]);
        
        // Sort root folders alphabetically
        uasort($rootFolders, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
        
        // Process each root folder and its children
        foreach ($rootFolders as $rootFolder) {
            \Log::info('Processing root folder', ['name' => $rootFolder['name'], 'id' => $rootFolder['id']]);
            $this->addFolderToHierarchy($rootFolder, $allFolders, $formattedFolders, 0, $rootFolder['name']);
        }
        
        return $formattedFolders;
    }

    /**
     * Build simple folder hierarchy with one level of children
     */
    private function buildSimpleFolderHierarchy(array $folders): array
    {
        $formattedFolders = [];
        $folderLookup = [];
        $childrenLookup = [];
        $allFolderIds = [];
        
        // Build lookup tables
        foreach ($folders as $folder) {
            $folderLookup[$folder['id']] = $folder;
            $allFolderIds[] = $folder['id'];
            $parentId = $folder['parentFolderId'] ?? 'root';
            if (!isset($childrenLookup[$parentId])) {
                $childrenLookup[$parentId] = [];
            }
            $childrenLookup[$parentId][] = $folder['id'];
        }
        
        // Find actual root folders (folders whose parentFolderId doesn't exist in our folder list)
        $rootFolderIds = [];
        foreach ($folders as $folder) {
            $parentId = $folder['parentFolderId'] ?? null;
            if ($parentId === null || !in_array($parentId, $allFolderIds)) {
                $rootFolderIds[] = $folder['id'];
            }
        }
        
        \Log::info('Folder lookup built', [
            'total_folders' => count($folderLookup),
            'root_folders_found' => count($rootFolderIds),
            'children_groups' => count($childrenLookup),
            'sample_parent_ids' => array_slice(array_unique(array_column($folders, 'parentFolderId')), 0, 5)
        ]);
        
        // Add root folders first
        if (!empty($rootFolderIds)) {
            
            // Sort root folders
            usort($rootFolderIds, function($a, $b) use ($folderLookup) {
                return strcasecmp($folderLookup[$a]['displayName'], $folderLookup[$b]['displayName']);
            });
            
            foreach ($rootFolderIds as $rootFolderId) {
                $rootFolder = $folderLookup[$rootFolderId];
                
                // Add root folder
                $formattedFolders[] = [
                    'id' => $rootFolder['id'],
                    'name' => $rootFolder['displayName'],
                    'original_name' => $rootFolder['displayName'],
                    'full_path' => $rootFolder['displayName'],
                    'parent_id' => null,
                    'total_count' => $rootFolder['totalItemCount'] ?? 0,
                    'unread_count' => $rootFolder['unreadItemCount'] ?? 0,
                    'level' => 0,
                    'sort_key' => $rootFolder['displayName'],
                ];
                
                // Add child folders if any exist in our retrieved folders
                $childFolders = [];
                foreach ($folders as $folder) {
                    if (($folder['parentFolderId'] ?? null) === $rootFolderId) {
                        $childFolders[] = $folder;
                    }
                }
                
                if (!empty($childFolders)) {
                    // Sort child folders
                    usort($childFolders, function($a, $b) {
                        return strcasecmp($a['displayName'], $b['displayName']);
                    });
                    
                    foreach ($childFolders as $childFolder) {
                        $fullPath = $rootFolder['displayName'] . '/' . $childFolder['displayName'];
                        
                        $formattedFolders[] = [
                            'id' => $childFolder['id'],
                            'name' => '  ' . $childFolder['displayName'], // 2 space indent
                            'original_name' => $childFolder['displayName'],
                            'full_path' => $fullPath,
                            'parent_id' => $rootFolderId,
                            'total_count' => $childFolder['totalItemCount'] ?? 0,
                            'unread_count' => $childFolder['unreadItemCount'] ?? 0,
                            'level' => 1,
                            'sort_key' => $fullPath,
                        ];
                    }
                }
            }
        }
        
        return $formattedFolders;
    }


    /**
     * Format folders with simple hierarchy detection (old method)
     */
    private function formatFoldersWithHierarchy(array $folders): array
    {
        $formattedFolders = [];
        
        // Create lookup arrays
        $folderLookup = [];
        $childrenLookup = [];
        
        foreach ($folders as $folder) {
            $folderLookup[$folder['id']] = [
                'id' => $folder['id'],
                'name' => $folder['displayName'],
                'original_name' => $folder['displayName'],
                'parent_id' => $folder['parentFolderId'] ?? null,
                'total_count' => $folder['totalItemCount'] ?? 0,
                'unread_count' => $folder['unreadItemCount'] ?? 0,
            ];
            
            $parentId = $folder['parentFolderId'] ?? 'root';
            if (!isset($childrenLookup[$parentId])) {
                $childrenLookup[$parentId] = [];
            }
            $childrenLookup[$parentId][] = $folder['id'];
        }
        
        // Build hierarchy starting from root folders
        $this->addFoldersToHierarchy('root', $folderLookup, $childrenLookup, $formattedFolders, 0, '');
        
        return $formattedFolders;
    }

    /**
     * Recursively add folders to hierarchy with indentation
     */
    private function addFoldersToHierarchy(string $parentId, array $folderLookup, array $childrenLookup, array &$formattedFolders, int $level, string $parentPath): void
    {
        if (!isset($childrenLookup[$parentId])) {
            return;
        }
        
        $children = $childrenLookup[$parentId];
        
        // Sort children by name
        usort($children, function($a, $b) use ($folderLookup) {
            return strcasecmp($folderLookup[$a]['name'], $folderLookup[$b]['name']);
        });
        
        foreach ($children as $childId) {
            if (!isset($folderLookup[$childId])) {
                continue;
            }
            
            $folder = $folderLookup[$childId];
            $indent = str_repeat('  ', $level);
            $path = $parentPath ? $parentPath . '/' . $folder['name'] : $folder['name'];
            
            $formattedFolders[] = [
                'id' => $folder['id'],
                'name' => $indent . $folder['name'],
                'original_name' => $folder['name'],
                'full_path' => $path,
                'parent_id' => $folder['parent_id'],
                'total_count' => $folder['total_count'],
                'unread_count' => $folder['unread_count'],
                'level' => $level,
                'sort_key' => $path,
            ];
            
            // Recursively add children
            $this->addFoldersToHierarchy($childId, $folderLookup, $childrenLookup, $formattedFolders, $level + 1, $path);
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
