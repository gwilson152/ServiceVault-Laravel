<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSystemConfig;
use App\Models\EmailDomainMapping;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class EmailSystemController extends Controller
{
    /**
     * Get the current email system configuration
     */
    public function getConfig(): JsonResponse
    {
        $config = EmailSystemConfig::getConfig();
        
        return response()->json($config);
    }

    /**
     * Update the email system configuration
     */
    public function updateConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // System status
            'system_active' => 'boolean',
            
            // Incoming configuration
            'incoming_enabled' => 'boolean',
            'incoming_provider' => 'nullable|string|in:imap,gmail,outlook,exchange',
            'incoming_host' => 'nullable|string|max:255',
            'incoming_port' => 'nullable|integer|min:1|max:65535',
            'incoming_username' => 'nullable|string|max:255',
            'incoming_password' => 'nullable|string|max:255',
            'incoming_encryption' => 'nullable|in:tls,ssl,starttls,none',
            'incoming_folder' => 'nullable|string|max:100',
            
            // Outgoing configuration
            'outgoing_enabled' => 'boolean',
            'outgoing_provider' => 'nullable|string|in:smtp,gmail,outlook,ses,sendgrid,postmark,mailgun',
            'outgoing_host' => 'nullable|string|max:255',
            'outgoing_port' => 'nullable|integer|min:1|max:65535',
            'outgoing_username' => 'nullable|string|max:255',
            'outgoing_password' => 'nullable|string|max:255',
            'outgoing_encryption' => 'nullable|in:tls,ssl,starttls,none',
            
            // From address configuration
            'from_address' => 'nullable|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'reply_to_address' => 'nullable|email|max:255',
            
            // Processing settings
            'auto_create_tickets' => 'boolean',
            'process_commands' => 'boolean',
            'send_confirmations' => 'boolean',
            'max_retries' => 'integer|min:0|max:10',
        ]);

        // Add user who updated this configuration
        $validated['updated_by_id'] = auth()->id();

        $config = EmailSystemConfig::getConfig();
        $config->update($validated);

        return response()->json([
            'message' => 'Email system configuration updated successfully',
            'config' => $config->fresh()
        ]);
    }

    /**
     * Test the email system configuration
     */
    public function testConfig(Request $request): JsonResponse
    {
        // Get the configuration (either current saved config or test data from request)
        if ($request->has('test_data')) {
            // Create a temporary config object for testing
            $config = new EmailSystemConfig($request->input('test_data', []));
        } else {
            $config = EmailSystemConfig::getConfig();
        }

        $results = $config->testConfiguration();

        return response()->json($results);
    }

    /**
     * Get domain mappings
     */
    public function getDomainMappings(): JsonResponse
    {
        $mappings = EmailDomainMapping::with(['account', 'defaultAssignedUser', 'createdBy'])
            ->byPriority()
            ->get();

        return response()->json($mappings);
    }

    /**
     * Create a new domain mapping
     */
    public function createDomainMapping(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain_pattern' => 'required|string|max:255',
            'pattern_type' => 'required|in:domain,email,wildcard',
            'account_id' => 'required|uuid|exists:accounts,id',
            'default_assigned_user_id' => 'nullable|uuid|exists:users,id',
            'default_category' => 'nullable|string|max:255',
            'default_priority' => 'nullable|in:low,medium,high,urgent',
            'auto_create_tickets' => 'boolean',
            'send_auto_reply' => 'boolean',
            'auto_reply_template' => 'nullable|string',
            'is_active' => 'boolean',
            'priority' => 'integer|min:1|max:1000',
        ]);

        // Validate pattern format
        $patternErrors = EmailDomainMapping::validatePattern(
            $validated['domain_pattern'], 
            $validated['pattern_type']
        );

        if (!empty($patternErrors)) {
            return response()->json([
                'message' => 'Invalid domain pattern',
                'errors' => ['domain_pattern' => $patternErrors]
            ], 422);
        }

        $validated['created_by_id'] = auth()->id();

        $mapping = EmailDomainMapping::create($validated);

        return response()->json([
            'message' => 'Domain mapping created successfully',
            'mapping' => $mapping->load(['account', 'defaultAssignedUser', 'createdBy'])
        ], 201);
    }

    /**
     * Update a domain mapping
     */
    public function updateDomainMapping(Request $request, EmailDomainMapping $mapping): JsonResponse
    {
        $validated = $request->validate([
            'domain_pattern' => 'sometimes|string|max:255',
            'pattern_type' => 'sometimes|in:domain,email,wildcard',
            'account_id' => 'sometimes|uuid|exists:accounts,id',
            'default_assigned_user_id' => 'nullable|uuid|exists:users,id',
            'default_category' => 'nullable|string|max:255',
            'default_priority' => 'nullable|in:low,medium,high,urgent',
            'auto_create_tickets' => 'boolean',
            'send_auto_reply' => 'boolean',
            'auto_reply_template' => 'nullable|string',
            'is_active' => 'boolean',
            'priority' => 'integer|min:1|max:1000',
        ]);

        // Validate pattern format if changed
        if (isset($validated['domain_pattern']) || isset($validated['pattern_type'])) {
            $pattern = $validated['domain_pattern'] ?? $mapping->domain_pattern;
            $type = $validated['pattern_type'] ?? $mapping->pattern_type;
            
            $patternErrors = EmailDomainMapping::validatePattern($pattern, $type);
            
            if (!empty($patternErrors)) {
                return response()->json([
                    'message' => 'Invalid domain pattern',
                    'errors' => ['domain_pattern' => $patternErrors]
                ], 422);
            }
        }

        $mapping->update($validated);

        return response()->json([
            'message' => 'Domain mapping updated successfully',
            'mapping' => $mapping->fresh()->load(['account', 'defaultAssignedUser', 'createdBy'])
        ]);
    }

    /**
     * Delete a domain mapping
     */
    public function deleteDomainMapping(EmailDomainMapping $mapping): JsonResponse
    {
        $mapping->delete();

        return response()->json([
            'message' => 'Domain mapping deleted successfully'
        ]);
    }

    /**
     * Test domain mapping for an email address
     */
    public function testDomainMapping(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_address' => 'required|email',
        ]);

        $mapping = EmailDomainMapping::findMatchingMapping($validated['email_address']);

        if ($mapping) {
            return response()->json([
                'matched' => true,
                'mapping' => $mapping->load(['account', 'defaultAssignedUser']),
                'routing_info' => $mapping->getRoutingInfo()
            ]);
        }

        return response()->json([
            'matched' => false,
            'message' => 'No matching domain mapping found for this email address'
        ]);
    }

    /**
     * Get pattern examples for domain mapping types
     */
    public function getPatternExamples(): JsonResponse
    {
        return response()->json(EmailDomainMapping::getPatternExamples());
    }

    /**
     * Get email system status and health information
     */
    public function getSystemStatus(): JsonResponse
    {
        $config = EmailSystemConfig::getConfig();
        
        return response()->json([
            'system_active' => $config->system_active,
            'fully_configured' => $config->isFullyConfigured(),
            'incoming_configured' => $config->hasIncomingConfigured(),
            'outgoing_configured' => $config->hasOutgoingConfigured(),
            'last_tested_at' => $config->last_tested_at,
            'test_results' => $config->test_results,
            'domain_mappings_count' => EmailDomainMapping::active()->count(),
        ]);
    }

    /**
     * Get provider-specific default configurations
     */
    public function getProviderDefaults(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|string',
            'type' => 'required|in:incoming,outgoing'
        ]);

        $defaults = EmailSystemConfig::getProviderDefaults(
            $validated['provider'], 
            $validated['type']
        );

        return response()->json($defaults);
    }
}