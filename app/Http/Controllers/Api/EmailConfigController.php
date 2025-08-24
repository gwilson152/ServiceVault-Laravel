<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailConfig;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmailConfigController extends Controller
{
    /**
     * Display a listing of email configurations
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmailConfig::class);

        $query = EmailConfig::with(['account']);

        // Filter by account
        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Filter by driver type
        if ($request->has('driver')) {
            $query->where('driver', $request->driver);
        }

        // Filter by direction
        if ($request->has('direction')) {
            $query->where('direction', $request->direction);
        }

        // Search by name or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        $configs = $query->orderBy('created_at', 'desc')
                        ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $configs->items(),
            'meta' => [
                'current_page' => $configs->currentPage(),
                'last_page' => $configs->lastPage(),
                'per_page' => $configs->perPage(),
                'total' => $configs->total(),
            ],
        ]);
    }

    /**
     * Store a newly created email configuration
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', EmailConfig::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'direction' => 'required|in:incoming,outgoing,both',
            'driver' => 'required|in:smtp,ses,postmark,mailgun,log',
            'account_id' => 'nullable|exists:accounts,id',
            
            // Connection settings
            'host' => 'required_if:driver,smtp|nullable|string|max:255',
            'port' => 'required_if:driver,smtp|nullable|integer|min:1|max:65535',
            'encryption' => 'nullable|in:tls,ssl',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            
            // Email addresses
            'from_address' => 'required|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'reply_to_address' => 'nullable|email|max:255',
            'reply_to_name' => 'nullable|string|max:255',
            
            // Advanced settings
            'timeout' => 'nullable|integer|min:1|max:300',
            'local_domain' => 'nullable|string|max:255',
            'verify_ssl' => 'nullable|boolean',
            'additional_options' => 'nullable|array',
            
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            
            // If setting as default, unset other defaults for the same account/direction
            if ($data['is_default'] ?? false) {
                EmailConfig::where('account_id', $data['account_id'] ?? null)
                          ->where('direction', $data['direction'])
                          ->update(['is_default' => false]);
            }

            $config = EmailConfig::create($data);

            return response()->json([
                'success' => true,
                'data' => $config->load('account'),
                'message' => 'Email configuration created successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create email configuration', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create email configuration',
                'message' => 'An error occurred while creating the configuration',
            ], 500);
        }
    }

    /**
     * Display the specified email configuration
     */
    public function show(EmailConfig $emailConfig): JsonResponse
    {
        $this->authorize('view', $emailConfig);

        return response()->json([
            'success' => true,
            'data' => $emailConfig->load('account'),
        ]);
    }

    /**
     * Update the specified email configuration
     */
    public function update(Request $request, EmailConfig $emailConfig): JsonResponse
    {
        $this->authorize('update', $emailConfig);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:500',
            'direction' => 'sometimes|required|in:incoming,outgoing,both',
            'driver' => 'sometimes|required|in:smtp,ses,postmark,mailgun,log',
            
            // Connection settings
            'host' => 'required_if:driver,smtp|nullable|string|max:255',
            'port' => 'required_if:driver,smtp|nullable|integer|min:1|max:65535',
            'encryption' => 'nullable|in:tls,ssl',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            
            // Email addresses
            'from_address' => 'sometimes|required|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'reply_to_address' => 'nullable|email|max:255',
            'reply_to_name' => 'nullable|string|max:255',
            
            // Advanced settings
            'timeout' => 'nullable|integer|min:1|max:300',
            'local_domain' => 'nullable|string|max:255',
            'verify_ssl' => 'nullable|boolean',
            'additional_options' => 'nullable|array',
            
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            
            // If setting as default, unset other defaults for the same account/direction
            if (($data['is_default'] ?? false) && isset($data['direction'])) {
                EmailConfig::where('account_id', $emailConfig->account_id)
                          ->where('direction', $data['direction'])
                          ->where('id', '!=', $emailConfig->id)
                          ->update(['is_default' => false]);
            }

            $emailConfig->update($data);

            return response()->json([
                'success' => true,
                'data' => $emailConfig->fresh()->load('account'),
                'message' => 'Email configuration updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update email configuration', [
                'config_id' => $emailConfig->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update email configuration',
                'message' => 'An error occurred while updating the configuration',
            ], 500);
        }
    }

    /**
     * Remove the specified email configuration
     */
    public function destroy(EmailConfig $emailConfig): JsonResponse
    {
        $this->authorize('delete', $emailConfig);

        try {
            $emailConfig->delete();

            return response()->json([
                'success' => true,
                'message' => 'Email configuration deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete email configuration', [
                'config_id' => $emailConfig->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete email configuration',
                'message' => 'An error occurred while deleting the configuration',
            ], 500);
        }
    }

    /**
     * Test email configuration connection
     */
    public function testConnection(Request $request, EmailConfig $emailConfig): JsonResponse
    {
        $this->authorize('update', $emailConfig);

        try {
            $config = $emailConfig->getMailConfig();
            
            // Test the connection by attempting to configure mail
            config(['mail.mailers.test' => $config]);
            
            // For SMTP, try to connect
            if ($config['transport'] === 'smtp') {
                $transport = app()->make(\Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport::class, [
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'encryption' => $config['encryption'],
                ]);
                
                if ($config['username']) {
                    $transport->setUsername($config['username']);
                    $transport->setPassword($config['password']);
                }
                
                // Test connection (this will throw if it fails)
                $transport->start();
                $transport->stop();
            }

            return response()->json([
                'success' => true,
                'message' => 'Email configuration test successful',
                'connection_status' => 'connected',
            ]);

        } catch (\Exception $e) {
            Log::warning('Email configuration test failed', [
                'config_id' => $emailConfig->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Connection test failed',
                'message' => $e->getMessage(),
                'connection_status' => 'failed',
            ], 400);
        }
    }

    /**
     * Send a test email using the configuration
     */
    public function sendTestEmail(Request $request, EmailConfig $emailConfig): JsonResponse
    {
        $this->authorize('update', $emailConfig);

        $validator = Validator::make($request->all(), [
            'to_email' => 'required|email',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $toEmail = $request->to_email;
            $subject = $request->subject ?? 'ServiceVault Email Configuration Test';
            $message = $request->message ?? 'This is a test email from ServiceVault email configuration.';

            // Temporarily set mail configuration
            $config = $emailConfig->getMailConfig();
            config(['mail.mailers.test' => $config]);
            config(['mail.default' => 'test']);

            // Send test email
            Mail::raw($message, function ($mail) use ($toEmail, $subject, $emailConfig) {
                $mail->to($toEmail)
                     ->subject($subject)
                     ->from($emailConfig->from_address, $emailConfig->from_name ?? 'ServiceVault');
                     
                if ($emailConfig->reply_to_address) {
                    $mail->replyTo($emailConfig->reply_to_address, $emailConfig->reply_to_name);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully',
                'sent_to' => $toEmail,
                'sent_from' => $emailConfig->from_address,
            ]);

        } catch (\Exception $e) {
            Log::error('Test email sending failed', [
                'config_id' => $emailConfig->id,
                'to_email' => $request->to_email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Test email failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available email drivers and their requirements
     */
    public function getDriverInfo(): JsonResponse
    {
        $drivers = [
            'smtp' => [
                'name' => 'SMTP',
                'description' => 'Standard SMTP server connection',
                'required_fields' => ['host', 'port', 'from_address'],
                'optional_fields' => ['username', 'password', 'encryption', 'timeout'],
                'supports' => ['incoming', 'outgoing'],
            ],
            'ses' => [
                'name' => 'Amazon SES',
                'description' => 'Amazon Simple Email Service',
                'required_fields' => ['from_address', 'username', 'password'],
                'optional_fields' => ['region', 'timeout'],
                'supports' => ['incoming', 'outgoing'],
            ],
            'postmark' => [
                'name' => 'Postmark',
                'description' => 'Postmark transactional email service',
                'required_fields' => ['from_address', 'password'],
                'optional_fields' => ['timeout'],
                'supports' => ['incoming', 'outgoing'],
            ],
            'mailgun' => [
                'name' => 'Mailgun',
                'description' => 'Mailgun email service',
                'required_fields' => ['from_address', 'username', 'password'],
                'optional_fields' => ['domain', 'timeout'],
                'supports' => ['incoming', 'outgoing'],
            ],
            'log' => [
                'name' => 'Log',
                'description' => 'Log emails to file (development only)',
                'required_fields' => ['from_address'],
                'optional_fields' => [],
                'supports' => ['outgoing'],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $drivers,
        ]);
    }

    /**
     * Set configuration as default for account/direction
     */
    public function setDefault(EmailConfig $emailConfig): JsonResponse
    {
        $this->authorize('update', $emailConfig);

        try {
            // Unset other defaults for same account/direction
            EmailConfig::where('account_id', $emailConfig->account_id)
                      ->where('direction', $emailConfig->direction)
                      ->where('id', '!=', $emailConfig->id)
                      ->update(['is_default' => false]);

            $emailConfig->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration set as default successfully',
                'data' => $emailConfig->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to set email configuration as default', [
                'config_id' => $emailConfig->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to set as default',
                'message' => 'An error occurred while updating the configuration',
            ], 500);
        }
    }
}