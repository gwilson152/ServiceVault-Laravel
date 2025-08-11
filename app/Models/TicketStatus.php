<?php

namespace App\Models;

use App\Traits\HasUuid;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStatus extends Model
{
    /** @use HasFactory<\Database\Factories\TicketStatusFactory> */
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
        'is_closed',
        'is_billable',
        'sort_order',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_closed' => 'boolean',
        'is_billable' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * Get tickets with this status
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'status', 'key');
    }

    /**
     * Scope for active statuses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered statuses
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope for open statuses (not closed)
     */
    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    /**
     * Scope for closed statuses
     */
    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    /**
     * Get the default ticket status
     */
    public static function getDefault(): ?TicketStatus
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * Get status options for forms
     */
    public static function getOptions(): array
    {
        return static::active()
            ->ordered()
            ->get()
            ->map(fn($status) => [
                'value' => $status->key,
                'label' => $status->name,
                'color' => $status->color,
                'bg_color' => $status->bg_color,
                'icon' => $status->icon,
                'is_closed' => $status->is_closed
            ])
            ->toArray();
    }

    /**
     * Get workflow transitions
     */
    public static function getWorkflowTransitions(): array
    {
        return [
            'open' => ['in_progress', 'cancelled'],
            'in_progress' => ['waiting_customer', 'resolved', 'cancelled', 'on_hold'],
            'waiting_customer' => ['in_progress', 'resolved'],
            'on_hold' => ['in_progress', 'cancelled'],
            'resolved' => ['closed', 'in_progress'], // Can reopen if customer isn't satisfied
            'closed' => [], // Closed tickets can't be transitioned (except by admin override)
            'cancelled' => [] // Cancelled tickets are final
        ];
    }

    /**
     * Check if this status can transition to another
     */
    public function canTransitionTo(string $newStatusKey): bool
    {
        $transitions = self::getWorkflowTransitions();
        return in_array($newStatusKey, $transitions[$this->key] ?? []);
    }

    /**
     * Get available next statuses for this status
     */
    public function getNextStatuses(): array
    {
        $transitions = self::getWorkflowTransitions();
        $nextKeys = $transitions[$this->key] ?? [];
        
        return self::whereIn('key', $nextKeys)
            ->active()
            ->ordered()
            ->get()
            ->toArray();
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure only one default status
        static::saving(function ($status) {
            if ($status->is_default) {
                static::where('id', '!=', $status->id)
                    ->update(['is_default' => false]);
            }
        });

        // Prevent deletion of statuses that are in use
        static::deleting(function ($status) {
            if ($status->tickets()->exists()) {
                throw new \Exception("Cannot delete status '{$status->name}' because it is being used by existing tickets.");
            }
        });
    }
}
