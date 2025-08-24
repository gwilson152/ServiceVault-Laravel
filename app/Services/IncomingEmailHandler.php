<?php

namespace App\Services;

use App\Jobs\ProcessIncomingEmail;
use App\Models\DomainMapping;
use App\Models\EmailProcessingLog;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\EmailParser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IncomingEmailHandler
{
    private EmailParser $parser;
    private EmailService $emailService;

    public function __construct(EmailParser $parser, EmailService $emailService)
    {
        $this->parser = $parser;
        $this->emailService = $emailService;
    }

    /**
     * Handle incoming email webhook/ingestion
     */
    public function handleIncomingEmail(string $rawEmail): array
    {
        try {
            // Parse the raw email
            $parsedEmail = $this->parser->parse($rawEmail);
            
            // Prepare email data for processing
            $emailData = $this->prepareEmailData($parsedEmail);
            
            // Dispatch to queue for processing
            ProcessIncomingEmail::dispatch($emailData);
            
            Log::info('Incoming email queued for processing', [
                'email_id' => $emailData['email_id'],
                'from' => $emailData['from'],
                'subject' => $emailData['subject'],
            ]);

            return [
                'success' => true,
                'email_id' => $emailData['email_id'],
                'queued' => true,
                'message' => 'Email queued for processing',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to handle incoming email', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to process incoming email',
            ];
        }
    }

    /**
     * Process incoming email immediately (synchronous)
     */
    public function processEmailImmediate(string $rawEmail): EmailProcessingLog
    {
        $parsedEmail = $this->parser->parse($rawEmail);
        $emailData = $this->prepareEmailData($parsedEmail);
        
        return $this->emailService->processIncomingEmail($emailData);
    }

    /**
     * Prepare parsed email data for processing
     */
    private function prepareEmailData(array $parsedEmail): array
    {
        return [
            'email_id' => (string) Str::uuid(),
            'direction' => 'incoming',
            'from' => $parsedEmail['from'] ?? '',
            'to' => $parsedEmail['to'] ?? [],
            'cc' => $parsedEmail['cc'] ?? [],
            'bcc' => $parsedEmail['bcc'] ?? [],
            'subject' => $parsedEmail['subject'] ?? '',
            'message_id' => $parsedEmail['message_id'] ?? null,
            'in_reply_to' => $parsedEmail['in_reply_to'] ?? null,
            'references' => $parsedEmail['references'] ?? [],
            'received_at' => $parsedEmail['date'] ?? now(),
            'body_text' => $parsedEmail['body_text'] ?? '',
            'body_html' => $parsedEmail['body_html'] ?? '',
            'attachments' => $this->processAttachments($parsedEmail['attachments'] ?? []),
            'raw_content' => $parsedEmail['raw_content'] ?? '',
        ];
    }

    /**
     * Process attachments for storage
     */
    private function processAttachments(array $attachments): array
    {
        $processedAttachments = [];

        foreach ($attachments as $attachment) {
            $processedAttachments[] = [
                'filename' => $attachment['filename'] ?? 'attachment',
                'content_type' => $attachment['content_type'] ?? 'application/octet-stream',
                'size' => $attachment['size'] ?? 0,
                'storage_path' => $attachment['storage_path'] ?? null,
                'encoding' => $attachment['encoding'] ?? null,
                'disposition' => $attachment['disposition'] ?? 'attachment',
            ];
        }

        return $processedAttachments;
    }

    /**
     * Determine account from email domain
     */
    public function determineAccountFromEmail(string $emailAddress): ?string
    {
        $domainMapping = DomainMapping::findMatchingDomain($emailAddress);
        
        return $domainMapping?->account_id;
    }

    /**
     * Find existing ticket from email data
     */
    public function findExistingTicket(array $emailData): ?Ticket
    {
        // Strategy 1: Look for ticket number in subject
        $subject = $emailData['subject'] ?? '';
        if (preg_match('/TKT-\d{4}-\d{4}/', $subject, $matches)) {
            $ticket = Ticket::where('ticket_number', $matches[0])->first();
            if ($ticket) {
                return $ticket;
            }
        }

        // Strategy 2: Check in-reply-to header for existing email processing logs
        $inReplyTo = $emailData['in_reply_to'] ?? '';
        if ($inReplyTo) {
            $existingLog = EmailProcessingLog::where('message_id', $inReplyTo)
                                           ->whereNotNull('ticket_id')
                                           ->first();
            if ($existingLog && $existingLog->ticket) {
                return $existingLog->ticket;
            }
        }

        // Strategy 3: Check references header chain
        $references = $emailData['references'] ?? [];
        if (!empty($references)) {
            $existingLog = EmailProcessingLog::whereIn('message_id', $references)
                                           ->whereNotNull('ticket_id')
                                           ->orderBy('created_at', 'desc')
                                           ->first();
            if ($existingLog && $existingLog->ticket) {
                return $existingLog->ticket;
            }
        }

        // Strategy 4: Look for recent tickets from same sender (within 24 hours)
        $fromEmail = $this->extractEmailAddress($emailData['from'] ?? '');
        if ($fromEmail) {
            $recentTicket = Ticket::where('customer_email', $fromEmail)
                                 ->where('created_at', '>=', now()->subHours(24))
                                 ->whereNotIn('status', ['closed', 'cancelled'])
                                 ->orderBy('created_at', 'desc')
                                 ->first();
            
            if ($recentTicket) {
                Log::info('Found recent ticket from same sender', [
                    'ticket_id' => $recentTicket->id,
                    'ticket_number' => $recentTicket->ticket_number,
                    'sender' => $fromEmail,
                ]);
                
                return $recentTicket;
            }
        }

        return null;
    }

    /**
     * Create new ticket from email
     */
    public function createTicketFromEmail(array $emailData, ?string $accountId = null): Ticket
    {
        $fromEmail = $this->extractEmailAddress($emailData['from'] ?? '');
        $fromName = $this->extractNameFromAddress($emailData['from'] ?? '');
        
        // Find or create customer user
        $customer = null;
        if ($fromEmail) {
            $customer = User::where('email', $fromEmail)->first();
        }

        // Parse any initial commands from subject
        $commands = $this->emailService->parseSubjectCommands($emailData['subject'] ?? '');
        
        // Set defaults
        $priority = 'normal';
        $category = 'support';
        $status = 'open';
        
        // Apply commands
        foreach ($commands as $command) {
            switch ($command['command']) {
                case 'priority':
                    if (in_array($command['value'], ['low', 'normal', 'medium', 'high', 'urgent'])) {
                        $priority = $command['value'];
                    }
                    break;
                case 'category':
                    if (in_array($command['value'], ['support', 'maintenance', 'development', 'consulting', 'other'])) {
                        $category = $command['value'];
                    }
                    break;
                case 'status':
                    if (in_array($command['value'], ['open', 'in_progress', 'waiting_customer'])) {
                        $status = $command['value'];
                    }
                    break;
            }
        }

        $ticket = Ticket::create([
            'account_id' => $accountId,
            'title' => $this->cleanSubjectForTitle($emailData['subject'] ?? 'Email Support Request'),
            'description' => $this->getBestBodyContent($emailData),
            'status' => $status,
            'priority' => $priority,
            'category' => $category,
            'customer_id' => $customer?->id,
            'customer_name' => $fromName ?: $fromEmail,
            'customer_email' => $fromEmail,
        ]);

        Log::info('Created ticket from email', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'from' => $fromEmail,
            'subject' => $emailData['subject'] ?? '',
        ]);

        return $ticket;
    }

    /**
     * Add email as comment to existing ticket
     */
    public function addEmailCommentToTicket(Ticket $ticket, array $emailData): TicketComment
    {
        $fromEmail = $this->extractEmailAddress($emailData['from'] ?? '');
        $fromName = $this->extractNameFromAddress($emailData['from'] ?? '');
        
        // Find the user who sent the email
        $user = null;
        if ($fromEmail) {
            $user = User::where('email', $fromEmail)->first();
        }

        $comment = $ticket->comments()->create([
            'user_id' => $user?->id,
            'content' => $this->getBestBodyContent($emailData),
            'is_internal' => false,
            'author_name' => $fromName ?: $fromEmail,
            'author_email' => $fromEmail,
            'metadata' => [
                'source' => 'email',
                'message_id' => $emailData['message_id'] ?? null,
                'has_attachments' => !empty($emailData['attachments']),
                'attachment_count' => count($emailData['attachments'] ?? []),
            ],
        ]);

        Log::info('Added email comment to ticket', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'comment_id' => $comment->id,
            'from' => $fromEmail,
        ]);

        return $comment;
    }

    /**
     * Get best body content from email data
     */
    private function getBestBodyContent(array $emailData): string
    {
        $textBody = $emailData['body_text'] ?? '';
        
        if (!empty($textBody)) {
            return $this->cleanEmailBody($textBody);
        }
        
        $htmlBody = $emailData['body_html'] ?? '';
        if (!empty($htmlBody)) {
            return $this->cleanEmailBody($this->extractTextFromHtml($htmlBody));
        }
        
        return 'Email content could not be parsed.';
    }

    /**
     * Clean email body content
     */
    private function cleanEmailBody(string $body): string
    {
        // Remove common email signatures and footers
        $patterns = [
            '/^--\s*$/m',  // Email signature separator
            '/^\s*From:.*$/m',  // Forwarded email headers
            '/^\s*Sent:.*$/m',
            '/^\s*To:.*$/m',
            '/^\s*Subject:.*$/m',
        ];
        
        foreach ($patterns as $pattern) {
            $parts = preg_split($pattern, $body, 2);
            if (count($parts) > 1) {
                $body = trim($parts[0]);
            }
        }
        
        // Remove excessive whitespace
        $body = preg_replace('/\n\s*\n\s*\n/', "\n\n", $body);
        
        return trim($body);
    }

    /**
     * Extract text from HTML
     */
    private function extractTextFromHtml(string $html): string
    {
        // Remove scripts and styles
        $html = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', '', $html);
        
        // Convert line breaks
        $html = preg_replace('/<br[^>]*>/i', "\n", $html);
        $html = preg_replace('/<\/?(p|div|h[1-6])[^>]*>/i', "\n", $html);
        
        // Strip remaining HTML tags
        $text = strip_tags($html);
        
        // Clean up whitespace
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);
        
        return trim($text);
    }

    /**
     * Extract email address from "Name <email>" format
     */
    private function extractEmailAddress(string $emailString): string
    {
        if (preg_match('/<(.+?)>/', $emailString, $matches)) {
            return strtolower(trim($matches[1]));
        }
        
        return strtolower(trim($emailString));
    }

    /**
     * Extract name from "Name <email>" format
     */
    private function extractNameFromAddress(string $emailString): ?string
    {
        if (preg_match('/^(.+?)\s*</', $emailString, $matches)) {
            return trim($matches[1], '"\'');
        }
        
        return null;
    }

    /**
     * Clean subject line for use as ticket title
     */
    private function cleanSubjectForTitle(string $subject): string
    {
        // Remove Re:, Fw:, etc.
        $subject = preg_replace('/^(Re|Fw|Fwd):\s*/i', '', $subject);
        
        // Remove commands from title
        $subject = preg_replace('/\w+:\w+/', '', $subject);
        
        // Clean up whitespace
        $subject = preg_replace('/\s+/', ' ', $subject);
        
        return trim($subject) ?: 'Email Support Request';
    }

    /**
     * Check if email should be processed
     */
    public function shouldProcessEmail(array $emailData): bool
    {
        $fromEmail = $this->extractEmailAddress($emailData['from'] ?? '');
        
        // Skip emails from system/daemon addresses
        $systemAddresses = [
            'noreply',
            'no-reply',
            'donotreply',
            'daemon',
            'postmaster',
            'mailer-daemon',
            'bounce',
            'bounces',
        ];
        
        foreach ($systemAddresses as $systemAddress) {
            if (strpos($fromEmail, $systemAddress) !== false) {
                Log::info('Skipping system email', [
                    'from' => $fromEmail,
                    'reason' => 'system address',
                ]);
                return false;
            }
        }
        
        // Skip if subject indicates auto-reply
        $subject = strtolower($emailData['subject'] ?? '');
        $autoReplyPatterns = [
            'out of office',
            'auto-reply',
            'automatic reply',
            'vacation',
            'away',
            'delivery failure',
            'undelivered',
            'bounce',
        ];
        
        foreach ($autoReplyPatterns as $pattern) {
            if (strpos($subject, $pattern) !== false) {
                Log::info('Skipping auto-reply email', [
                    'from' => $fromEmail,
                    'subject' => $emailData['subject'] ?? '',
                    'reason' => 'auto-reply pattern: ' . $pattern,
                ]);
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get email processing statistics
     */
    public function getProcessingStats(int $days = 7): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'total_processed' => EmailProcessingLog::incoming()
                ->where('created_at', '>=', $startDate)
                ->count(),
            'successful' => EmailProcessingLog::incoming()
                ->processed()
                ->where('created_at', '>=', $startDate)
                ->count(),
            'failed' => EmailProcessingLog::incoming()
                ->failed()
                ->where('created_at', '>=', $startDate)
                ->count(),
            'tickets_created' => EmailProcessingLog::incoming()
                ->where('created_new_ticket', true)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'comments_added' => EmailProcessingLog::incoming()
                ->where('created_new_ticket', false)
                ->whereNotNull('ticket_comment_id')
                ->where('created_at', '>=', $startDate)
                ->count(),
        ];
    }
}