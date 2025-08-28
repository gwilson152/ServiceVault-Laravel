<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class EmailDomainMapping extends Model
{
    use HasUuid;
    protected $fillable = [
        'domain',
        'account_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the business account this mapping routes to
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the default assigned user
     */
    public function defaultAssignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assigned_user_id');
    }

    /**
     * Get the user who created this mapping
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Scope to only active mappings
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order (lowest first)
     */
    public function scopeByOrder(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'asc');
    }

    /**
     * Find the best matching domain mapping for an email address
     */
    public static function findMatchingMapping(string $emailAddress): ?EmailDomainMapping
    {
        $domain = substr(strrchr($emailAddress, "@"), 1); // Extract domain part
        
        // Get all active mappings ordered by sort order
        $mappings = static::active()
            ->byOrder()
            ->get();

        foreach ($mappings as $mapping) {
            if ($mapping->matches($emailAddress, $domain)) {
                return $mapping;
            }
        }

        return null;
    }

    /**
     * Auto-detect pattern type from the domain pattern
     */
    public function getPatternTypeAttribute(): string
    {
        return static::detectPatternType($this->domain);
    }
    
    /**
     * Alias for backward compatibility
     */
    public function getDomainPatternAttribute(): string
    {
        return $this->domain;
    }
    
    /**
     * Detect pattern type from a domain pattern string
     */
    public static function detectPatternType(string $pattern): string
    {
        // Check for wildcards
        if (str_contains($pattern, '*')) {
            return 'wildcard';
        }
        
        // Check if it's a full email address
        if (str_contains($pattern, '@') && !str_starts_with($pattern, '@')) {
            return 'email';
        }
        
        // Default to domain pattern (@example.com or example.com)
        return 'domain';
    }

    /**
     * Check if this mapping matches the given email address
     */
    public function matches(string $emailAddress, ?string $domain = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $domain = $domain ?? substr(strrchr($emailAddress, "@"), 1);
        $patternType = $this->pattern_type; // Uses the accessor above
        
        switch ($patternType) {
            case 'email':
                // Exact email match: support@acme.com
                return strcasecmp($emailAddress, $this->domain) === 0;
                
            case 'domain':
                // Domain match: @acme.com or acme.com
                $pattern = ltrim($this->domain, '@');
                return strcasecmp($domain, $pattern) === 0;
                
            case 'wildcard':
                // Wildcard match: *@acme.com or *.acme.com
                $pattern = str_replace('*', '.*', preg_quote($this->domain, '/'));
                return preg_match("/^{$pattern}$/i", $emailAddress) === 1;
                
            default:
                return false;
        }
    }

    /**
     * Get routing information for this mapping
     */
    public function getRoutingInfo(): array
    {
        return [
            'account_id' => $this->account_id,
            'account_name' => $this->account->name ?? null,
            'default_assigned_user_id' => $this->default_assigned_user_id,
            'default_assigned_user_name' => $this->defaultAssignedUser->name ?? null,
            'default_category' => $this->default_category,
            'default_priority' => $this->default_priority,
            'auto_create_tickets' => $this->auto_create_tickets,
            'send_auto_reply' => $this->send_auto_reply,
            'auto_reply_template' => $this->auto_reply_template,
            'custom_rules' => $this->custom_rules,
        ];
    }

    /**
     * Create a default mapping for all emails to go to a default account
     */
    public static function createDefaultMapping(string $accountId, ?string $userId = null): EmailDomainMapping
    {
        return static::create([
            'domain_pattern' => '*@*',
            'pattern_type' => 'wildcard',
            'account_id' => $accountId,
            'default_assigned_user_id' => $userId,
            'default_category' => 'general',
            'default_priority' => 'medium',
            'auto_create_tickets' => true,
            'send_auto_reply' => false,
            'is_active' => true,
            'priority' => 1, // Lowest priority as catch-all
        ]);
    }

    /**
     * Validate domain pattern format
     */
    public static function validatePattern(string $pattern, string $type): array
    {
        $errors = [];

        switch ($type) {
            case 'email':
                if (!filter_var($pattern, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email address format';
                }
                break;
                
            case 'domain':
                $domain = ltrim($pattern, '@');
                if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/', $domain)) {
                    $errors[] = 'Invalid domain format';
                }
                break;
                
            case 'wildcard':
                // Basic validation for wildcard patterns
                if (strpos($pattern, '*') === false) {
                    $errors[] = 'Wildcard pattern must contain at least one *';
                }
                if (!preg_match('/^[\w\*@\.\-]+$/', $pattern)) {
                    $errors[] = 'Invalid characters in wildcard pattern';
                }
                break;
        }

        return $errors;
    }

    /**
     * Get example patterns for each type
     */
    public static function getPatternExamples(): array
    {
        return [
            'email' => [
                'pattern' => 'support@acme.com',
                'description' => 'Match exact email address',
                'matches' => ['support@acme.com'],
                'doesnt_match' => ['info@acme.com', 'support@other.com']
            ],
            'domain' => [
                'pattern' => '@acme.com',
                'description' => 'Match all emails from domain',
                'matches' => ['support@acme.com', 'info@acme.com', 'sales@acme.com'],
                'doesnt_match' => ['support@other.com', 'info@different.com']
            ],
            'wildcard' => [
                'pattern' => '*@acme.com',
                'description' => 'Match all emails from domain (wildcard syntax)',
                'matches' => ['support@acme.com', 'info@acme.com', 'any@acme.com'],
                'doesnt_match' => ['support@other.com', 'info@different.com']
            ]
        ];
    }
}