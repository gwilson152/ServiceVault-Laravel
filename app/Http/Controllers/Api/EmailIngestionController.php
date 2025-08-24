<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IncomingEmailHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EmailIngestionController extends Controller
{
    private IncomingEmailHandler $emailHandler;

    public function __construct(IncomingEmailHandler $emailHandler)
    {
        $this->emailHandler = $emailHandler;
    }

    /**
     * Webhook endpoint for receiving raw emails
     * This endpoint can be used by external email services like:
     * - SendGrid Parse Webhook
     * - Mailgun Routes
     * - AWS SES
     * - Postmark Inbound
     * - Custom SMTP servers
     */
    public function receiveEmail(Request $request): JsonResponse
    {
        try {
            // Log the incoming request for debugging
            Log::info('Email ingestion webhook called', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'content_type' => $request->header('Content-Type'),
                'content_length' => $request->header('Content-Length'),
            ]);

            // Get raw email content from different possible sources
            $rawEmail = $this->extractRawEmail($request);
            
            if (empty($rawEmail)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No email content found in request',
                    'message' => 'Raw email data is required',
                ], 400);
            }

            // Validate email content
            if (!$this->isValidEmailContent($rawEmail)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid email format',
                    'message' => 'Email content does not appear to be valid RFC 2822 format',
                ], 400);
            }

            // Process the email
            $result = $this->emailHandler->handleIncomingEmail($rawEmail);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'email_id' => $result['email_id'],
                    'message' => $result['message'],
                    'processing_status' => 'queued',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Processing failed',
                    'message' => $result['message'] ?? 'Failed to process email',
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Email ingestion endpoint error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'An error occurred while processing the email',
            ], 500);
        }
    }

    /**
     * SendGrid Parse Webhook format
     */
    public function receiveSendGrid(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Missing required SendGrid fields',
                'errors' => $validator->errors(),
            ], 400);
        }

        $rawEmail = $request->input('email');
        
        return $this->processEmailContent($rawEmail, 'sendgrid');
    }

    /**
     * Mailgun Incoming Email format
     */
    public function receiveMailgun(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'body-mime' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Missing required Mailgun fields',
                'errors' => $validator->errors(),
            ], 400);
        }

        $rawEmail = $request->input('body-mime');
        
        return $this->processEmailContent($rawEmail, 'mailgun');
    }

    /**
     * Postmark Inbound format
     */
    public function receivePostmark(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'RawEmail' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Missing required Postmark fields',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Postmark sends base64 encoded raw email
        $rawEmail = base64_decode($request->input('RawEmail'));
        
        return $this->processEmailContent($rawEmail, 'postmark');
    }

    /**
     * AWS SES format
     */
    public function receiveAwsSes(Request $request): JsonResponse
    {
        // AWS SES can send via SNS or direct
        $content = $request->getContent();
        
        if ($request->header('x-amz-sns-message-type')) {
            // SNS notification format
            $snsData = json_decode($content, true);
            
            if (!$snsData || !isset($snsData['Message'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid SNS message format',
                ], 400);
            }
            
            $message = json_decode($snsData['Message'], true);
            
            if (!isset($message['content'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'No email content in SNS message',
                ], 400);
            }
            
            $rawEmail = $message['content'];
        } else {
            // Direct format
            $rawEmail = $content;
        }
        
        return $this->processEmailContent($rawEmail, 'aws-ses');
    }

    /**
     * Process immediate email (synchronous)
     */
    public function processImmediate(Request $request): JsonResponse
    {
        try {
            $rawEmail = $this->extractRawEmail($request);
            
            if (empty($rawEmail)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No email content found',
                ], 400);
            }

            // Process immediately instead of queuing
            $processingLog = $this->emailHandler->processEmailImmediate($rawEmail);

            return response()->json([
                'success' => true,
                'email_id' => $processingLog->email_id,
                'processing_status' => $processingLog->status,
                'ticket_id' => $processingLog->ticket_id,
                'ticket_number' => $processingLog->ticket?->ticket_number,
                'actions_taken' => $processingLog->actions_taken,
                'processing_time_ms' => $processingLog->processing_duration_ms,
            ]);

        } catch (\Exception $e) {
            Log::error('Immediate email processing failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'processing_status' => 'failed',
            ], 500);
        }
    }

    /**
     * Get processing status of an email
     */
    public function getStatus(string $emailId): JsonResponse
    {
        $processingLog = \App\Models\EmailProcessingLog::where('email_id', $emailId)->first();

        if (!$processingLog) {
            return response()->json([
                'success' => false,
                'error' => 'Email not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'email_id' => $processingLog->email_id,
            'status' => $processingLog->status,
            'processed_at' => $processingLog->processed_at,
            'ticket_id' => $processingLog->ticket_id,
            'ticket_number' => $processingLog->ticket?->ticket_number,
            'actions_taken' => $processingLog->actions_taken,
            'error_message' => $processingLog->error_message,
            'retry_count' => $processingLog->retry_count,
            'next_retry_at' => $processingLog->next_retry_at,
        ]);
    }

    /**
     * Health check endpoint
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'service' => 'Email Ingestion',
            'version' => '1.0.0',
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Extract raw email from various request formats
     */
    private function extractRawEmail(Request $request): ?string
    {
        // Try different common field names
        $possibleFields = [
            'email',           // SendGrid
            'raw_email',       // Generic
            'body-mime',       // Mailgun
            'RawEmail',        // Postmark
            'message',         // Generic
            'content',         // AWS SES
        ];

        foreach ($possibleFields as $field) {
            $value = $request->input($field);
            if (!empty($value)) {
                return $value;
            }
        }

        // Try raw request body
        $rawBody = $request->getContent();
        if (!empty($rawBody)) {
            // Check if it looks like an email (has headers)
            if (strpos($rawBody, 'From:') !== false || strpos($rawBody, 'To:') !== false) {
                return $rawBody;
            }
        }

        return null;
    }

    /**
     * Validate if content looks like a valid email
     */
    private function isValidEmailContent(string $content): bool
    {
        // Basic validation - look for essential email headers
        $requiredPatterns = [
            '/^(From|To|Subject):/mi',
        ];

        foreach ($requiredPatterns as $pattern) {
            if (!preg_match($pattern, $content)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Process email content with source tracking
     */
    private function processEmailContent(string $rawEmail, string $source): JsonResponse
    {
        try {
            Log::info('Processing email from source', [
                'source' => $source,
                'content_length' => strlen($rawEmail),
            ]);

            $result = $this->emailHandler->handleIncomingEmail($rawEmail);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'email_id' => $result['email_id'],
                    'source' => $source,
                    'message' => $result['message'],
                    'processing_status' => 'queued',
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'source' => $source,
                    'error' => $result['error'] ?? 'Processing failed',
                    'message' => $result['message'] ?? 'Failed to process email',
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Email processing failed for source', [
                'source' => $source,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'source' => $source,
                'error' => 'Processing error',
                'message' => 'An error occurred while processing the email',
            ], 500);
        }
    }
}
