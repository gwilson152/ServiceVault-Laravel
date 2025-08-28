<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainMapping extends Model
{
    // Specify the table name
    protected $table = 'email_domain_mappings';

    /** @use HasFactory<\Database\Factories\DomainMappingFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'domain_pattern',
        'account_id',
        'role_template_id',
        'is_active',
        'sort_order',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the account that owns the domain mapping.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the role template for this domain mapping.
     */
    public function roleTemplate(): BelongsTo
    {
        return $this->belongsTo(RoleTemplate::class);
    }

    /**
     * Scope a query to only include active domain mappings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by sort order (lowest first - top to bottom).
     */
    public function scopeByOrder($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'asc');
    }

    /**
     * Check if a domain pattern matches a given email domain.
     */
    public function matchesDomain(string $emailDomain): bool
    {
        $pattern = $this->domain_pattern;

        // Convert wildcard pattern to regex
        if (str_contains($pattern, '*')) {
            // Escape special regex characters except *
            $regexPattern = preg_quote($pattern, '/');
            // Convert * to regex equivalent
            $regexPattern = str_replace('\*', '[^.]*', $regexPattern);
            // Add anchors
            $regexPattern = '/^'.$regexPattern.'$/i';

            return preg_match($regexPattern, $emailDomain) === 1;
        }

        // Exact match (case insensitive)
        return strcasecmp($pattern, $emailDomain) === 0;
    }

    /**
     * Find the best matching domain mapping for an email address.
     */
    public static function findMatchingDomain(string $email): ?DomainMapping
    {
        $emailDomain = strtolower(substr(strrchr($email, '@'), 1));

        if (! $emailDomain) {
            return null;
        }

        // Get all active domain mappings ordered by sort order
        $mappings = self::active()
            ->with(['account', 'roleTemplate'])
            ->byOrder()
            ->get();

        // Find the first matching pattern
        foreach ($mappings as $mapping) {
            if ($mapping->matchesDomain($emailDomain)) {
                return $mapping;
            }
        }

        return null;
    }

    /**
     * Get all domains that would match this pattern (for display purposes).
     */
    public function getExampleDomains(): array
    {
        $pattern = $this->domain_pattern;

        if (str_contains($pattern, '*')) {
            // For wildcard patterns, show some examples
            $base = str_replace('*.', '', $pattern);

            return [
                "subdomain.{$base}",
                "mail.{$base}",
                "team.{$base}",
                "dev.{$base}",
            ];
        }

        return [$pattern];
    }
}
