<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'key',
        'template_type',
        'subject',
        'body_text',
        'body_html',
        'variables',
        'conditions',
        'is_active',
        'is_default',
        'priority',
        'language',
        'timezone',
        'include_signature',
        'include_footer',
        'auto_html_from_text',
        'usage_count',
        'last_used_at',
        'description',
        'tags',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'variables' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'priority' => 'integer',
        'include_signature' => 'boolean',
        'include_footer' => 'boolean',
        'auto_html_from_text' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('template_type', $type);
    }

    public function scopeForAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('account_id');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('created_at', 'asc');
    }

    public function scopeByLanguage($query, string $language = 'en')
    {
        return $query->where('language', $language);
    }

    /**
     * Template Processing Methods
     */
    public function render(array $variables = []): array
    {
        // Track usage
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);

        $processedSubject = $this->processTemplate($this->subject, $variables);
        $processedBodyText = $this->processTemplate($this->body_text, $variables);
        
        $processedBodyHtml = null;
        if ($this->body_html) {
            $processedBodyHtml = $this->processTemplate($this->body_html, $variables);
        } elseif ($this->auto_html_from_text) {
            $processedBodyHtml = $this->convertTextToHtml($processedBodyText);
        }

        return [
            'subject' => $processedSubject,
            'body_text' => $processedBodyText,
            'body_html' => $processedBodyHtml,
        ];
    }

    /**
     * Process template with flexible tag syntax
     * Supports: {{variable}}, {variable}, [variable], $variable$
     */
    private function processTemplate(string $template, array $variables): string
    {
        $processed = $template;

        // Process each variable with multiple tag formats
        foreach ($variables as $key => $value) {
            $stringValue = $this->convertValueToString($value);
            
            // Support multiple tag formats for flexibility
            $tagFormats = [
                '{{' . $key . '}}',     // {{variable}} - Handlebars/Mustache style
                '{' . $key . '}',       // {variable} - Simple curly braces
                '[' . $key . ']',       // [variable] - Square brackets
                '$' . $key . '$',       // $variable$ - Dollar signs
                '${' . $key . '}',      // ${variable} - Shell-style
                '%' . $key . '%',       // %variable% - Windows batch style
            ];

            foreach ($tagFormats as $tag) {
                $processed = str_replace($tag, $stringValue, $processed);
            }
        }

        // Process conditional blocks: {{#if condition}}content{{/if}}
        $processed = $this->processConditionalBlocks($processed, $variables);

        // Process loops: {{#each items}}{{name}}{{/each}}
        $processed = $this->processLoopBlocks($processed, $variables);

        // Process date formatting: {{date:Y-m-d}}
        $processed = $this->processDateFormatting($processed, $variables);

        return $processed;
    }

    /**
     * Process conditional blocks
     */
    private function processConditionalBlocks(string $template, array $variables): string
    {
        // Match {{#if variable}}content{{/if}} blocks
        return preg_replace_callback(
            '/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s',
            function ($matches) use ($variables) {
                $condition = $matches[1];
                $content = $matches[2];
                
                if (isset($variables[$condition]) && $variables[$condition]) {
                    return $content;
                }
                
                return '';
            },
            $template
        );
    }

    /**
     * Process loop blocks
     */
    private function processLoopBlocks(string $template, array $variables): string
    {
        // Match {{#each items}}{{name}}{{/each}} blocks
        return preg_replace_callback(
            '/\{\{#each\s+(\w+)\}\}(.*?)\{\{\/each\}\}/s',
            function ($matches) use ($variables) {
                $arrayKey = $matches[1];
                $itemTemplate = $matches[2];
                
                if (!isset($variables[$arrayKey]) || !is_array($variables[$arrayKey])) {
                    return '';
                }
                
                $output = '';
                foreach ($variables[$arrayKey] as $item) {
                    if (is_array($item)) {
                        $itemOutput = $itemTemplate;
                        foreach ($item as $key => $value) {
                            $itemOutput = str_replace('{{' . $key . '}}', $this->convertValueToString($value), $itemOutput);
                        }
                        $output .= $itemOutput;
                    } else {
                        $output .= str_replace('{{.}}', $this->convertValueToString($item), $itemTemplate);
                    }
                }
                
                return $output;
            },
            $template
        );
    }

    /**
     * Process date formatting
     */
    private function processDateFormatting(string $template, array $variables): string
    {
        // Match {{date:Y-m-d}} or {{created_at:M j, Y}}
        return preg_replace_callback(
            '/\{\{(\w+):([^}]+)\}\}/',
            function ($matches) use ($variables) {
                $dateKey = $matches[1];
                $format = $matches[2];
                
                if (!isset($variables[$dateKey])) {
                    return $matches[0]; // Return original if variable not found
                }
                
                $date = $variables[$dateKey];
                if ($date instanceof \Carbon\Carbon || $date instanceof \DateTime) {
                    return $date->format($format);
                } elseif (is_string($date)) {
                    try {
                        return \Carbon\Carbon::parse($date)->format($format);
                    } catch (\Exception $e) {
                        return $date; // Return original string if not parseable
                    }
                }
                
                return $matches[0];
            },
            $template
        );
    }

    /**
     * Convert value to string representation
     */
    private function convertValueToString($value): string
    {
        if (is_null($value)) {
            return '';
        }
        
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        
        if (is_array($value)) {
            return implode(', ', array_map([$this, 'convertValueToString'], $value));
        }
        
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            } elseif ($value instanceof \Carbon\Carbon || $value instanceof \DateTime) {
                return $value->format('Y-m-d H:i:s');
            }
            
            return '[Object]';
        }
        
        return (string) $value;
    }

    /**
     * Convert plain text to HTML
     */
    private function convertTextToHtml(string $text): string
    {
        // Convert line breaks to <br> tags
        $html = nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        
        // Wrap in basic HTML structure
        return "<div style=\"font-family: Arial, sans-serif; line-height: 1.6;\">{$html}</div>";
    }

    /**
     * Get available template variables for this template type
     */
    public function getAvailableVariables(): array
    {
        $baseVariables = [
            'account_name' => 'Account name',
            'account_email' => 'Account email address',
            'current_date' => 'Current date',
            'current_time' => 'Current time',
            'system_name' => 'System name (Service Vault)',
        ];

        $typeSpecificVariables = match ($this->template_type) {
            'ticket_created', 'ticket_updated', 'ticket_assigned', 'ticket_resolved' => [
                'ticket_number' => 'Ticket number',
                'ticket_title' => 'Ticket title',
                'ticket_description' => 'Ticket description',
                'ticket_status' => 'Ticket status',
                'ticket_priority' => 'Ticket priority',
                'ticket_category' => 'Ticket category',
                'ticket_created_at' => 'Ticket creation date',
                'ticket_due_date' => 'Ticket due date',
                'agent_name' => 'Assigned agent name',
                'agent_email' => 'Assigned agent email',
                'customer_name' => 'Customer name',
                'customer_email' => 'Customer email',
            ],
            'comment_added' => [
                'ticket_number' => 'Ticket number',
                'ticket_title' => 'Ticket title',
                'comment_author' => 'Comment author name',
                'comment_content' => 'Comment content',
                'comment_date' => 'Comment date',
            ],
            'command_confirmation', 'command_error' => [
                'command' => 'Command that was executed',
                'result' => 'Command result/error message',
                'ticket_number' => 'Related ticket number (if applicable)',
            ],
            default => []
        };

        return array_merge($baseVariables, $typeSpecificVariables);
    }

    /**
     * Find the best template for a specific type and account
     */
    public static function getBestTemplate(string $templateType, ?string $accountId = null, string $language = 'en'): ?EmailTemplate
    {
        return self::active()
            ->byType($templateType)
            ->byLanguage($language)
            ->where(function ($query) use ($accountId) {
                $query->where('account_id', $accountId)
                      ->orWhereNull('account_id');
            })
            ->byPriority()
            ->first();
    }

    /**
     * Create default system templates
     */
    public static function createDefaultTemplates(): void
    {
        $defaultTemplates = [
            [
                'key' => 'ticket_created_default',
                'name' => 'New Ticket Created',
                'template_type' => 'ticket_created',
                'subject' => 'New Ticket Created: {{ticket_number}} - {{ticket_title}}',
                'body_text' => "Hello {{customer_name}},\n\nYour support ticket has been created successfully.\n\nTicket Details:\n- Number: {{ticket_number}}\n- Title: {{ticket_title}}\n- Priority: {{ticket_priority}}\n- Status: {{ticket_status}}\n\nDescription:\n{{ticket_description}}\n\nWe will review your request and get back to you soon.\n\nBest regards,\n{{account_name}} Support Team",
                'is_default' => true,
            ],
            [
                'key' => 'ticket_assigned_default',
                'name' => 'Ticket Assigned',
                'template_type' => 'ticket_assigned',
                'subject' => 'Ticket {{ticket_number}} has been assigned to {{agent_name}}',
                'body_text' => "Hello {{customer_name}},\n\nYour ticket {{ticket_number}} - \"{{ticket_title}}\" has been assigned to {{agent_name}}.\n\nOur agent will be in touch with you shortly to assist with your request.\n\nBest regards,\n{{account_name}} Support Team",
                'is_default' => true,
            ],
            [
                'key' => 'comment_added_default',
                'name' => 'New Comment Added',
                'template_type' => 'comment_added',
                'subject' => 'New comment on ticket {{ticket_number}}',
                'body_text' => "Hello,\n\nA new comment has been added to ticket {{ticket_number}} - \"{{ticket_title}}\".\n\nFrom: {{comment_author}}\nDate: {{comment_date:M j, Y g:i A}}\n\nComment:\n{{comment_content}}\n\nBest regards,\n{{account_name}} Support Team",
                'is_default' => true,
            ],
            [
                'key' => 'command_confirmation_default',
                'name' => 'Email Command Confirmation',
                'template_type' => 'command_confirmation',
                'subject' => 'Command executed successfully',
                'body_text' => "Hello,\n\nYour email command has been processed successfully.\n\nCommand: {{command}}\nResult: {{result}}\n\n{{#if ticket_number}}Ticket: {{ticket_number}}{{/if}}\n\nBest regards,\n{{account_name}} Support Team",
                'is_default' => true,
            ],
            [
                'key' => 'command_error_default',
                'name' => 'Email Command Error',
                'template_type' => 'command_error',
                'subject' => 'Command execution failed',
                'body_text' => "Hello,\n\nThere was an error processing your email command.\n\nCommand: {{command}}\nError: {{result}}\n\nPlease check the command syntax and try again, or contact support for assistance.\n\nBest regards,\n{{account_name}} Support Team",
                'is_default' => true,
            ],
        ];

        foreach ($defaultTemplates as $template) {
            self::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
