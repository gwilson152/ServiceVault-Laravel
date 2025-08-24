<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of email templates
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmailTemplate::class);

        $query = EmailTemplate::with(['account']);

        // Filter by account
        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Filter by template type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by language
        if ($request->has('language')) {
            $query->where('language', $request->language);
        }

        // Search by name or subject
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('subject', 'ILIKE', "%{$search}%");
            });
        }

        $templates = $query->orderBy('type')
                          ->orderBy('name')
                          ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $templates->items(),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
        ]);
    }

    /**
     * Store a newly created email template
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', EmailTemplate::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:ticket_created,ticket_updated,ticket_assigned,ticket_resolved,notification,welcome,password_reset',
            'subject' => 'required|string|max:255',
            'content_html' => 'required|string',
            'content_text' => 'nullable|string',
            'account_id' => 'nullable|exists:accounts,id',
            'language' => 'nullable|string|max:5|regex:/^[a-z]{2}(-[A-Z]{2})?$/',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:100',
            'is_active' => 'boolean',
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
            
            // Set default language if not provided
            if (empty($data['language'])) {
                $data['language'] = 'en';
            }

            // Generate text content from HTML if not provided
            if (empty($data['content_text']) && !empty($data['content_html'])) {
                $data['content_text'] = strip_tags($data['content_html']);
            }

            $template = EmailTemplate::create($data);

            return response()->json([
                'success' => true,
                'data' => $template->load('account'),
                'message' => 'Email template created successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create email template', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create email template',
                'message' => 'An error occurred while creating the template',
            ], 500);
        }
    }

    /**
     * Display the specified email template
     */
    public function show(EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('view', $emailTemplate);

        return response()->json([
            'success' => true,
            'data' => $emailTemplate->load('account'),
        ]);
    }

    /**
     * Update the specified email template
     */
    public function update(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('update', $emailTemplate);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:ticket_created,ticket_updated,ticket_assigned,ticket_resolved,notification,welcome,password_reset',
            'subject' => 'sometimes|required|string|max:255',
            'content_html' => 'sometimes|required|string',
            'content_text' => 'nullable|string',
            'language' => 'nullable|string|max:5|regex:/^[a-z]{2}(-[A-Z]{2})?$/',
            'variables' => 'nullable|array',
            'variables.*' => 'string|max:100',
            'is_active' => 'boolean',
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
            
            // Generate text content from HTML if HTML was updated but text wasn't provided
            if (isset($data['content_html']) && !isset($data['content_text'])) {
                $data['content_text'] = strip_tags($data['content_html']);
            }

            $emailTemplate->update($data);

            return response()->json([
                'success' => true,
                'data' => $emailTemplate->fresh()->load('account'),
                'message' => 'Email template updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update email template', [
                'template_id' => $emailTemplate->id,
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update email template',
                'message' => 'An error occurred while updating the template',
            ], 500);
        }
    }

    /**
     * Remove the specified email template
     */
    public function destroy(EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('delete', $emailTemplate);

        try {
            $emailTemplate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Email template deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete email template', [
                'template_id' => $emailTemplate->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete email template',
                'message' => 'An error occurred while deleting the template',
            ], 500);
        }
    }

    /**
     * Preview email template with sample data
     */
    public function preview(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('view', $emailTemplate);

        $validator = Validator::make($request->all(), [
            'variables' => 'nullable|array',
            'format' => 'nullable|in:html,text,both',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $variables = $request->input('variables', []);
            $format = $request->input('format', 'both');
            
            // Add default sample variables if none provided
            if (empty($variables)) {
                $variables = [
                    'ticket_number' => 'T-2025-001',
                    'ticket_subject' => 'Sample Ticket Subject',
                    'customer_name' => 'John Doe',
                    'agent_name' => 'Jane Smith',
                    'account_name' => 'Sample Account',
                    'status' => 'Open',
                    'priority' => 'High',
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ];
            }

            $preview = [];

            if ($format === 'html' || $format === 'both') {
                $preview['subject'] = $emailTemplate->processTemplate($emailTemplate->subject, $variables);
                $preview['content_html'] = $emailTemplate->processTemplate($emailTemplate->content_html, $variables);
            }

            if ($format === 'text' || $format === 'both') {
                $preview['content_text'] = $emailTemplate->processTemplate($emailTemplate->content_text, $variables);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'template' => $emailTemplate,
                    'preview' => $preview,
                    'variables_used' => $variables,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to preview email template', [
                'template_id' => $emailTemplate->id,
                'error' => $e->getMessage(),
                'variables' => $request->input('variables', []),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Preview failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available template types and their descriptions
     */
    public function getTemplateTypes(): JsonResponse
    {
        $types = [
            'ticket_created' => [
                'name' => 'Ticket Created',
                'description' => 'Sent when a new ticket is created',
                'variables' => ['ticket_number', 'ticket_subject', 'customer_name', 'account_name', 'created_at'],
            ],
            'ticket_updated' => [
                'name' => 'Ticket Updated',
                'description' => 'Sent when a ticket is updated',
                'variables' => ['ticket_number', 'ticket_subject', 'customer_name', 'agent_name', 'status', 'updated_at'],
            ],
            'ticket_assigned' => [
                'name' => 'Ticket Assigned',
                'description' => 'Sent when a ticket is assigned to an agent',
                'variables' => ['ticket_number', 'ticket_subject', 'customer_name', 'agent_name', 'assigned_at'],
            ],
            'ticket_resolved' => [
                'name' => 'Ticket Resolved',
                'description' => 'Sent when a ticket is resolved',
                'variables' => ['ticket_number', 'ticket_subject', 'customer_name', 'agent_name', 'resolved_at'],
            ],
            'notification' => [
                'name' => 'General Notification',
                'description' => 'General notification template',
                'variables' => ['recipient_name', 'message', 'sender_name', 'timestamp'],
            ],
            'welcome' => [
                'name' => 'Welcome Email',
                'description' => 'Welcome email for new users',
                'variables' => ['user_name', 'account_name', 'login_url'],
            ],
            'password_reset' => [
                'name' => 'Password Reset',
                'description' => 'Password reset email',
                'variables' => ['user_name', 'reset_url', 'expires_at'],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Duplicate an existing template
     */
    public function duplicate(EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('create', EmailTemplate::class);

        try {
            $duplicateData = $emailTemplate->toArray();
            unset($duplicateData['id']);
            unset($duplicateData['created_at']);
            unset($duplicateData['updated_at']);
            
            // Add "(Copy)" to the name
            $duplicateData['name'] = $emailTemplate->name . ' (Copy)';
            
            $duplicate = EmailTemplate::create($duplicateData);

            return response()->json([
                'success' => true,
                'data' => $duplicate->load('account'),
                'message' => 'Email template duplicated successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to duplicate email template', [
                'template_id' => $emailTemplate->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to duplicate template',
                'message' => 'An error occurred while duplicating the template',
            ], 500);
        }
    }
}