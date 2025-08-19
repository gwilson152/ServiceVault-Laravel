<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketPriority extends Model
{
    /** @use HasFactory<\Database\Factories\TicketPriorityFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'key',
        'name',
        'description',
        'color',
        'bg_color',
        'icon',
        'is_active',
        'is_default',
        'severity_level',
        'escalation_multiplier',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'severity_level' => 'integer',
        'escalation_multiplier' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Get tickets with this priority
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'priority', 'key');
    }

    /**
     * Scope for active priorities
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered priorities
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('severity_level');
    }

    /**
     * Scope for ordered by severity (highest first)
     */
    public function scopeBySeverity($query)
    {
        return $query->orderBy('severity_level', 'desc');
    }

    /**
     * Get the default ticket priority
     */
    public static function getDefault(): ?TicketPriority
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * Get priority options for forms
     */
    public static function getOptions(): array
    {
        return static::active()
            ->ordered()
            ->get()
            ->map(fn ($priority) => [
                'value' => $priority->key,
                'label' => $priority->name,
                'color' => $priority->color,
                'bg_color' => $priority->bg_color,
                'icon' => $priority->icon,
                'severity_level' => $priority->severity_level,
                'escalation_multiplier' => $priority->escalation_multiplier,
            ])
            ->toArray();
    }

    /**
     * Get priority by severity level
     */
    public static function getBySeverity(int $level): ?TicketPriority
    {
        return static::where('severity_level', $level)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure only one default priority
        static::saving(function ($priority) {
            if ($priority->is_default) {
                static::where('id', '!=', $priority->id)
                    ->update(['is_default' => false]);
            }
        });

        // Prevent deletion of priorities that are in use
        static::deleting(function ($priority) {
            if ($priority->tickets()->exists()) {
                throw new \Exception("Cannot delete priority '{$priority->name}' because it is being used by existing tickets.");
            }
        });
    }
}
