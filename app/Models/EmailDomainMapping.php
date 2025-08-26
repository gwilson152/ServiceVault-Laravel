<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class EmailDomainMapping extends Model
{
    protected $fillable = [
        'domain_pattern',
        'pattern_type',
        'account_id',
        'default_assigned_user_id',
        'default_category',
        'default_priority',
        'auto_create_tickets',
        'send_auto_reply',
        'auto_reply_template',
        'custom_rules',
        'is_active',
        'priority',
        'created_by_id',
    ];

    protected $casts = [
        'auto_create_tickets' => 'boolean',
        'send_auto_reply' => 'boolean',
        'is_active' => 'boolean',
        'custom_rules' => 'array',
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
     * Scope to order by priority (highest first)
     */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Find the best matching domain mapping for an email address
     */
    public static function findMatchingMapping(string $emailAddress): ?EmailDomainMapping
    {
        $domain = substr(strrchr($emailAddress, "@"), 1); // Extract domain part
        
        // Get all active mappings ordered by priority
        $mappings = static::active()
            ->byPriority()
            ->get();

        foreach ($mappings as $mapping) {
            if ($mapping->matches($emailAddress, $domain)) {
                return $mapping;
            }
        }

        return null;
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
        
        switch ($this->pattern_type) {
            case 'email':
                // Exact email match: support@acme.com
                return strcasecmp($emailAddress, $this->domain_pattern) === 0;
                
            case 'domain':
                // Domain match: @acme.com
                $pattern = ltrim($this->domain_pattern, '@');
                return strcasecmp($domain, $pattern) === 0;
                
            case 'wildcard':
                // Wildcard match: *@acme.com or *.acme.com
                $pattern = str_replace('*', '.*', preg_quote($this->domain_pattern, '/'));
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