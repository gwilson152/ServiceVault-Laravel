<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmailParser
{
    private array $parsedEmail;
    private string $rawContent;

    /**
     * Parse raw email content
     */
    public function parse(string $rawEmail): array
    {
        $this->rawContent = $rawEmail;
        $this->parsedEmail = $this->parseRawEmail($rawEmail);
        
        return $this->parsedEmail;
    }

    /**
     * Parse raw email into structured data
     */
    private function parseRawEmail(string $rawEmail): array
    {
        $headers = [];
        $body = '';
        $attachments = [];
        
        // Split headers and body
        $parts = preg_split('/\r?\n\r?\n/', $rawEmail, 2);
        $headerSection = $parts[0] ?? '';
        $bodySection = $parts[1] ?? '';

        // Parse headers
        $headers = $this->parseHeaders($headerSection);

        // Parse body based on content type
        $contentType = $headers['content-type'] ?? 'text/plain';
        
        if (strpos($contentType, 'multipart/') === 0) {
            // Multipart email
            $boundary = $this->extractBoundary($contentType);
            $bodyParts = $this->parseMultipart($bodySection, $boundary);
            
            $parsedBody = $this->extractBodyAndAttachments($bodyParts);
            $body = $parsedBody['body'];
            $attachments = $parsedBody['attachments'];
        } else {
            // Simple email
            $body = $this->decodeBody($bodySection, $headers);
        }

        return [
            'headers' => $headers,
            'from' => $this->parseEmailAddress($headers['from'] ?? ''),
            'to' => $this->parseEmailAddressList($headers['to'] ?? ''),
            'cc' => $this->parseEmailAddressList($headers['cc'] ?? ''),
            'bcc' => $this->parseEmailAddressList($headers['bcc'] ?? ''),
            'subject' => $this->decodeHeader($headers['subject'] ?? ''),
            'message_id' => trim($headers['message-id'] ?? '', '<>'),
            'in_reply_to' => trim($headers['in-reply-to'] ?? '', '<>'),
            'references' => $this->parseReferences($headers['references'] ?? ''),
            'date' => $this->parseDate($headers['date'] ?? ''),
            'body_text' => $body['text'] ?? '',
            'body_html' => $body['html'] ?? '',
            'attachments' => $attachments,
            'raw_content' => $rawEmail,
        ];
    }

    /**
     * Parse email headers
     */
    private function parseHeaders(string $headerSection): array
    {
        $headers = [];
        $lines = preg_split('/\r?\n/', $headerSection);
        $currentHeader = '';
        
        foreach ($lines as $line) {
            if (preg_match('/^([^:]+):\s*(.*)$/', $line, $matches)) {
                // New header
                $currentHeader = strtolower(trim($matches[1]));
                $headers[$currentHeader] = trim($matches[2]);
            } elseif ($currentHeader && (strpos($line, ' ') === 0 || strpos($line, "\t") === 0)) {
                // Continuation of previous header
                $headers[$currentHeader] .= ' ' . trim($line);
            }
        }
        
        return $headers;
    }

    /**
     * Extract boundary from content-type header
     */
    private function extractBoundary(string $contentType): string
    {
        if (preg_match('/boundary="([^"]+)"/', $contentType, $matches)) {
            return $matches[1];
        } elseif (preg_match('/boundary=([^\s;]+)/', $contentType, $matches)) {
            return trim($matches[1], '"');
        }
        
        return '';
    }

    /**
     * Parse multipart email content
     */
    private function parseMultipart(string $body, string $boundary): array
    {
        if (empty($boundary)) {
            return [];
        }

        $parts = [];
        $sections = explode('--' . $boundary, $body);
        
        foreach ($sections as $section) {
            $section = trim($section);
            if (empty($section) || $section === '--') {
                continue;
            }

            // Split headers and content
            $partParts = preg_split('/\r?\n\r?\n/', $section, 2);
            $partHeaders = $this->parseHeaders($partParts[0] ?? '');
            $partContent = $partParts[1] ?? '';

            $parts[] = [
                'headers' => $partHeaders,
                'content' => $partContent,
            ];
        }

        return $parts;
    }

    /**
     * Extract body text/HTML and attachments from multipart sections
     */
    private function extractBodyAndAttachments(array $parts): array
    {
        $body = ['text' => '', 'html' => ''];
        $attachments = [];

        foreach ($parts as $part) {
            $headers = $part['headers'];
            $content = $part['content'];
            
            $contentType = strtolower($headers['content-type'] ?? 'text/plain');
            $disposition = strtolower($headers['content-disposition'] ?? '');
            
            // Check if it's an attachment
            if (strpos($disposition, 'attachment') === 0 || strpos($disposition, 'inline') === 0) {
                $attachments[] = $this->processAttachment($part);
                continue;
            }

            // Process body parts
            if (strpos($contentType, 'text/plain') === 0) {
                $body['text'] = $this->decodeBody($content, $headers);
            } elseif (strpos($contentType, 'text/html') === 0) {
                $body['html'] = $this->decodeBody($content, $headers);
            } elseif (strpos($contentType, 'multipart/') === 0) {
                // Nested multipart
                $nestedBoundary = $this->extractBoundary($contentType);
                $nestedParts = $this->parseMultipart($content, $nestedBoundary);
                $nestedResult = $this->extractBodyAndAttachments($nestedParts);
                
                if (!empty($nestedResult['body']['text'])) {
                    $body['text'] = $nestedResult['body']['text'];
                }
                if (!empty($nestedResult['body']['html'])) {
                    $body['html'] = $nestedResult['body']['html'];
                }
                $attachments = array_merge($attachments, $nestedResult['attachments']);
            }
        }

        return [
            'body' => $body,
            'attachments' => $attachments,
        ];
    }

    /**
     * Process email attachment
     */
    private function processAttachment(array $part): array
    {
        $headers = $part['headers'];
        $content = $part['content'];
        
        $contentType = $headers['content-type'] ?? 'application/octet-stream';
        $disposition = $headers['content-disposition'] ?? '';
        $encoding = strtolower($headers['content-transfer-encoding'] ?? '');
        
        // Extract filename
        $filename = 'attachment';
        if (preg_match('/filename="([^"]+)"/', $disposition, $matches)) {
            $filename = $this->decodeHeader($matches[1]);
        } elseif (preg_match('/filename=([^\s;]+)/', $disposition, $matches)) {
            $filename = $this->decodeHeader(trim($matches[1], '"'));
        } elseif (preg_match('/name="([^"]+)"/', $contentType, $matches)) {
            $filename = $this->decodeHeader($matches[1]);
        }

        // Decode content based on encoding
        $decodedContent = $this->decodeContent($content, $encoding);
        
        // Store attachment
        $storagePath = $this->storeAttachment($filename, $decodedContent);
        
        return [
            'filename' => $filename,
            'content_type' => trim(explode(';', $contentType)[0]),
            'size' => strlen($decodedContent),
            'storage_path' => $storagePath,
            'encoding' => $encoding,
            'disposition' => $disposition,
        ];
    }

    /**
     * Store attachment file
     */
    private function storeAttachment(string $filename, string $content): string
    {
        $sanitizedFilename = $this->sanitizeFilename($filename);
        $directory = 'email-attachments/' . date('Y/m/d');
        $fullPath = $directory . '/' . Str::uuid() . '_' . $sanitizedFilename;
        
        Storage::disk('local')->put($fullPath, $content);
        
        return $fullPath;
    }

    /**
     * Sanitize filename for storage
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Limit length
        if (strlen($filename) > 100) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 90) . '.' . $extension;
        }
        
        return $filename;
    }

    /**
     * Decode email body content
     */
    private function decodeBody(string $content, array $headers): string
    {
        $encoding = strtolower($headers['content-transfer-encoding'] ?? '');
        $charset = $this->extractCharset($headers['content-type'] ?? '');
        
        $decoded = $this->decodeContent($content, $encoding);
        
        // Convert charset if needed
        if ($charset && $charset !== 'utf-8') {
            $converted = @mb_convert_encoding($decoded, 'UTF-8', $charset);
            if ($converted !== false) {
                $decoded = $converted;
            }
        }
        
        return trim($decoded);
    }

    /**
     * Decode content based on transfer encoding
     */
    private function decodeContent(string $content, string $encoding): string
    {
        switch ($encoding) {
            case 'base64':
                return base64_decode($content);
            case 'quoted-printable':
                return quoted_printable_decode($content);
            case '7bit':
            case '8bit':
            case 'binary':
            default:
                return $content;
        }
    }

    /**
     * Extract charset from content-type header
     */
    private function extractCharset(string $contentType): string
    {
        if (preg_match('/charset="?([^"\s;]+)"?/i', $contentType, $matches)) {
            return strtolower($matches[1]);
        }
        
        return 'utf-8';
    }

    /**
     * Decode encoded header (RFC 2047)
     */
    private function decodeHeader(string $header): string
    {
        if (preg_match_all('/=\?([^?]+)\?([BQ])\?([^?]*)\?=/', $header, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $charset = $match[1];
                $encoding = strtoupper($match[2]);
                $encodedText = $match[3];
                
                if ($encoding === 'B') {
                    $decoded = base64_decode($encodedText);
                } elseif ($encoding === 'Q') {
                    $decoded = quoted_printable_decode(str_replace('_', ' ', $encodedText));
                } else {
                    $decoded = $encodedText;
                }
                
                // Convert to UTF-8
                if (strtolower($charset) !== 'utf-8') {
                    $converted = @mb_convert_encoding($decoded, 'UTF-8', $charset);
                    if ($converted !== false) {
                        $decoded = $converted;
                    }
                }
                
                $header = str_replace($match[0], $decoded, $header);
            }
        }
        
        return trim($header);
    }

    /**
     * Parse email address
     */
    private function parseEmailAddress(string $address): string
    {
        $address = $this->decodeHeader($address);
        
        if (preg_match('/<(.+?)>/', $address, $matches)) {
            return trim($matches[1]);
        }
        
        return trim($address);
    }

    /**
     * Parse list of email addresses
     */
    private function parseEmailAddressList(string $addresses): array
    {
        if (empty($addresses)) {
            return [];
        }

        $addresses = $this->decodeHeader($addresses);
        $list = [];
        
        // Simple parsing - could be enhanced for complex cases
        $parts = preg_split('/,(?![^<]*>)/', $addresses);
        
        foreach ($parts as $part) {
            $email = $this->parseEmailAddress(trim($part));
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $list[] = $email;
            }
        }
        
        return $list;
    }

    /**
     * Parse References header
     */
    private function parseReferences(string $references): array
    {
        if (empty($references)) {
            return [];
        }

        $refs = [];
        preg_match_all('/<([^>]+)>/', $references, $matches);
        
        foreach ($matches[1] as $ref) {
            $refs[] = trim($ref);
        }
        
        return $refs;
    }

    /**
     * Parse date header
     */
    private function parseDate(string $dateString): ?\Carbon\Carbon
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get parsed email data
     */
    public function getParsedData(): array
    {
        return $this->parsedEmail;
    }

    /**
     * Get specific header value
     */
    public function getHeader(string $name): ?string
    {
        return $this->parsedEmail['headers'][strtolower($name)] ?? null;
    }

    /**
     * Get body text
     */
    public function getBodyText(): string
    {
        return $this->parsedEmail['body_text'] ?? '';
    }

    /**
     * Get body HTML
     */
    public function getBodyHtml(): string
    {
        return $this->parsedEmail['body_html'] ?? '';
    }

    /**
     * Get attachments
     */
    public function getAttachments(): array
    {
        return $this->parsedEmail['attachments'] ?? [];
    }

    /**
     * Check if email has attachments
     */
    public function hasAttachments(): bool
    {
        return !empty($this->parsedEmail['attachments']);
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCount(): int
    {
        return count($this->parsedEmail['attachments'] ?? []);
    }

    /**
     * Extract clean text from HTML content
     */
    public function extractTextFromHtml(string $html): string
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
        $text = trim($text);
        
        return $text;
    }

    /**
     * Get the best available body content (prefer text, fall back to HTML)
     */
    public function getBestBodyContent(): string
    {
        $textBody = $this->getBodyText();
        
        if (!empty($textBody)) {
            return $textBody;
        }
        
        $htmlBody = $this->getBodyHtml();
        if (!empty($htmlBody)) {
            return $this->extractTextFromHtml($htmlBody);
        }
        
        return '';
    }
}