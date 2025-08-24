<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailAttachmentHandler
{
    private array $allowedMimeTypes = [
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'application/rtf',
        
        // Images
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
        'image/svg+xml',
        
        // Archives
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/gzip',
        'application/x-tar',
        
        // Other common types
        'application/json',
        'application/xml',
        'text/xml',
        'text/html',
    ];

    private array $dangerousExtensions = [
        'exe', 'bat', 'cmd', 'com', 'scr', 'pif', 'vbs', 'js', 'jar',
        'sh', 'php', 'asp', 'aspx', 'jsp', 'py', 'pl', 'rb', 'ps1',
    ];

    private int $maxFileSize = 10485760; // 10MB in bytes
    private int $maxFilesPerEmail = 20;

    /**
     * Process attachments from parsed email
     */
    public function processEmailAttachments(
        array $attachments, 
        ?Ticket $ticket = null, 
        ?TicketComment $comment = null
    ): array {
        if (empty($attachments)) {
            return [];
        }

        if (count($attachments) > $this->maxFilesPerEmail) {
            Log::warning('Email has too many attachments', [
                'attachment_count' => count($attachments),
                'max_allowed' => $this->maxFilesPerEmail,
                'ticket_id' => $ticket?->id,
            ]);
            
            // Take only the first N attachments
            $attachments = array_slice($attachments, 0, $this->maxFilesPerEmail);
        }

        $processedAttachments = [];
        $errors = [];

        foreach ($attachments as $index => $attachment) {
            try {
                $result = $this->processAttachment($attachment, $ticket, $comment);
                
                if ($result['success']) {
                    $processedAttachments[] = $result['attachment'];
                } else {
                    $errors[] = [
                        'filename' => $attachment['filename'] ?? "attachment_$index",
                        'error' => $result['error'],
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Failed to process email attachment', [
                    'filename' => $attachment['filename'] ?? "attachment_$index",
                    'error' => $e->getMessage(),
                    'ticket_id' => $ticket?->id,
                ]);
                
                $errors[] = [
                    'filename' => $attachment['filename'] ?? "attachment_$index",
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'processed' => $processedAttachments,
            'errors' => $errors,
            'total_count' => count($attachments),
            'success_count' => count($processedAttachments),
            'error_count' => count($errors),
        ];
    }

    /**
     * Process a single attachment
     */
    private function processAttachment(
        array $attachment, 
        ?Ticket $ticket = null, 
        ?TicketComment $comment = null
    ): array {
        $filename = $attachment['filename'] ?? 'attachment';
        $contentType = $attachment['content_type'] ?? 'application/octet-stream';
        $size = $attachment['size'] ?? 0;
        $storagePath = $attachment['storage_path'] ?? null;

        // Validate file size
        if ($size > $this->maxFileSize) {
            return [
                'success' => false,
                'error' => "File too large: {$filename} ({$this->formatBytes($size)}). Maximum allowed: {$this->formatBytes($this->maxFileSize)}",
            ];
        }

        // Validate file type
        if (!$this->isAllowedMimeType($contentType)) {
            return [
                'success' => false,
                'error' => "File type not allowed: {$contentType} for file {$filename}",
            ];
        }

        // Check for dangerous extensions
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($extension, $this->dangerousExtensions)) {
            return [
                'success' => false,
                'error' => "Dangerous file extension: {$extension} for file {$filename}",
            ];
        }

        // Virus scan (placeholder - would integrate with actual antivirus)
        if (!$this->scanForViruses($storagePath)) {
            return [
                'success' => false,
                'error' => "Security scan failed for file: {$filename}",
            ];
        }

        // Move to permanent storage location
        $permanentPath = $this->moveToPermamentStorage($storagePath, $ticket, $comment);

        // Create attachment record
        $attachmentRecord = $this->createAttachmentRecord([
            'filename' => $filename,
            'content_type' => $contentType,
            'size' => $size,
            'storage_path' => $permanentPath,
            'ticket_id' => $ticket?->id,
            'comment_id' => $comment?->id,
            'original_path' => $storagePath,
        ]);

        return [
            'success' => true,
            'attachment' => $attachmentRecord,
        ];
    }

    /**
     * Check if MIME type is allowed
     */
    private function isAllowedMimeType(string $mimeType): bool
    {
        return in_array(strtolower($mimeType), $this->allowedMimeTypes);
    }

    /**
     * Placeholder virus scanning function
     */
    private function scanForViruses(?string $filePath): bool
    {
        if (!$filePath || !Storage::disk('local')->exists($filePath)) {
            return false;
        }

        // In production, this would integrate with ClamAV or another antivirus
        // For now, we'll just check file size and basic patterns
        
        $content = Storage::disk('local')->get($filePath);
        $size = strlen($content);
        
        // Reject files that are suspiciously large or small
        if ($size < 1 || $size > $this->maxFileSize) {
            return false;
        }
        
        // Basic pattern matching for known malicious signatures
        $maliciousPatterns = [
            'TVqQAAMAAAAEAAAA', // MZ header (Windows executable)
            '<!DOCTYPE html', // HTML files might contain scripts
            '<script', // JavaScript
            '<?php', // PHP code
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                Log::warning('Potentially malicious content detected', [
                    'file_path' => $filePath,
                    'pattern' => $pattern,
                ]);
                return false;
            }
        }
        
        return true;
    }

    /**
     * Move file to permanent storage
     */
    private function moveToPermamentStorage(
        ?string $tempPath, 
        ?Ticket $ticket = null, 
        ?TicketComment $comment = null
    ): string {
        if (!$tempPath || !Storage::disk('local')->exists($tempPath)) {
            throw new \Exception('Temporary file not found');
        }

        // Generate permanent path
        $directory = 'attachments';
        
        if ($ticket) {
            $directory .= '/tickets/' . $ticket->id;
        } elseif ($comment) {
            $directory .= '/comments/' . $comment->id;
        } else {
            $directory .= '/email/' . date('Y/m/d');
        }

        $filename = pathinfo($tempPath, PATHINFO_BASENAME);
        $permanentPath = $directory . '/' . $filename;

        // Move file
        Storage::disk('local')->move($tempPath, $permanentPath);

        return $permanentPath;
    }

    /**
     * Create attachment database record
     */
    private function createAttachmentRecord(array $data): array
    {
        // For now, return the data array
        // In a full implementation, you might have an Attachment model
        return [
            'id' => (string) Str::uuid(),
            'filename' => $data['filename'],
            'content_type' => $data['content_type'],
            'size' => $data['size'],
            'storage_path' => $data['storage_path'],
            'ticket_id' => $data['ticket_id'],
            'comment_id' => $data['comment_id'],
            'created_at' => now()->toISOString(),
            'download_url' => $this->generateDownloadUrl($data['storage_path']),
        ];
    }

    /**
     * Generate secure download URL
     */
    private function generateDownloadUrl(string $storagePath): string
    {
        // Generate a temporary signed URL
        return url("/attachments/download/" . base64_encode($storagePath));
    }

    /**
     * Get attachment by ID
     */
    public function getAttachment(string $attachmentId): ?array
    {
        // In a full implementation, this would query the Attachment model
        // For now, return null as placeholder
        return null;
    }

    /**
     * Delete attachment
     */
    public function deleteAttachment(string $attachmentId): bool
    {
        try {
            // In a full implementation:
            // 1. Find attachment record
            // 2. Delete file from storage
            // 3. Delete database record
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete attachment', [
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Get attachments for ticket
     */
    public function getTicketAttachments(string $ticketId): array
    {
        // In a full implementation, this would query attachments for the ticket
        return [];
    }

    /**
     * Get attachments for comment
     */
    public function getCommentAttachments(string $commentId): array
    {
        // In a full implementation, this would query attachments for the comment
        return [];
    }

    /**
     * Validate attachment upload
     */
    public function validateUpload(UploadedFile $file): array
    {
        $errors = [];

        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            $errors[] = "File too large. Maximum allowed: {$this->formatBytes($this->maxFileSize)}";
        }

        // Check MIME type
        if (!$this->isAllowedMimeType($file->getMimeType())) {
            $errors[] = "File type not allowed: {$file->getMimeType()}";
        }

        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, $this->dangerousExtensions)) {
            $errors[] = "Dangerous file extension: {$extension}";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Format file size in human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get allowed file types for display
     */
    public function getAllowedFileTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    /**
     * Get maximum file size
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * Get maximum files per email
     */
    public function getMaxFilesPerEmail(): int
    {
        return $this->maxFilesPerEmail;
    }

    /**
     * Update configuration
     */
    public function updateConfiguration(array $config): void
    {
        if (isset($config['max_file_size'])) {
            $this->maxFileSize = (int) $config['max_file_size'];
        }
        
        if (isset($config['max_files_per_email'])) {
            $this->maxFilesPerEmail = (int) $config['max_files_per_email'];
        }
        
        if (isset($config['allowed_mime_types']) && is_array($config['allowed_mime_types'])) {
            $this->allowedMimeTypes = $config['allowed_mime_types'];
        }
    }

    /**
     * Get attachment statistics
     */
    public function getAttachmentStats(int $days = 30): array
    {
        // In a full implementation, this would query the database
        return [
            'total_attachments' => 0,
            'total_size' => 0,
            'avg_size' => 0,
            'most_common_types' => [],
            'period_days' => $days,
        ];
    }

    /**
     * Clean up old attachments
     */
    public function cleanupOldAttachments(int $olderThanDays = 90): int
    {
        $cutoffDate = now()->subDays($olderThanDays);
        $deletedCount = 0;

        try {
            // In a full implementation:
            // 1. Query old attachment records
            // 2. Delete files from storage
            // 3. Delete database records
            // 4. Return count of deleted attachments
            
            Log::info('Attachment cleanup completed', [
                'older_than_days' => $olderThanDays,
                'deleted_count' => $deletedCount,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Attachment cleanup failed', [
                'error' => $e->getMessage(),
                'older_than_days' => $olderThanDays,
            ]);
        }

        return $deletedCount;
    }
}