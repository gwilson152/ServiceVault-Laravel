<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    /** @use HasFactory<\Database\Factories\TicketCategoryFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'description',
        'color',
        'sla_hours',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get tickets with this category
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category', 'key');
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered categories
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope for categories requiring approval
     */
    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    /**
     * Get the default ticket category
     */
    public static function getDefault(): ?TicketCategory
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * Get category options for forms
     */
    public static function getOptions(): array
    {
        return static::active()
            ->ordered()
            ->get()
            ->map(fn ($category) => [
                'key' => $category->key,
                'name' => $category->name,
                'description' => $category->description,
                'color' => $category->color,
                'bg_color' => $category->bg_color,
                'icon' => $category->icon,
                'requires_approval' => $category->requires_approval,
                'default_estimated_hours' => $category->default_estimated_hours,
                'sla_hours' => $category->sla_hours,
                'default_priority_multiplier' => $category->default_priority_multiplier,
            ])
            ->toArray();
    }

    /**
     * Get suggested priority based on category multiplier
     */
    public function getSuggestedPriority(string $basePriority = 'medium'): string
    {
        $priorities = ['low' => 1, 'medium' => 2, 'high' => 3, 'urgent' => 4];
        $priorityNames = array_flip($priorities);

        $baseValue = $priorities[$basePriority] ?? 2;
        $adjustedValue = round($baseValue * $this->default_priority_multiplier);
        $adjustedValue = max(1, min(4, $adjustedValue)); // Clamp between 1 and 4

        return $priorityNames[$adjustedValue];
    }

    /**
     * Check if SLA is breached for given creation time
     */
    public function isSlaBreached(\Carbon\Carbon $createdAt): bool
    {
        if (! $this->sla_hours) {
            return false;
        }

        return $createdAt->addHours($this->sla_hours)->isPast();
    }

    /**
     * Get SLA deadline for given creation time
     */
    public function getSlaDeadline(\Carbon\Carbon $createdAt): ?\Carbon\Carbon
    {
        if (! $this->sla_hours) {
            return null;
        }

        return $createdAt->addHours($this->sla_hours);
    }

    /**
     * Get formatted SLA hours
     */
    public function getFormattedSlaAttribute(): ?string
    {
        if (! $this->sla_hours) {
            return null;
        }

        if ($this->sla_hours < 24) {
            return "{$this->sla_hours} hours";
        }

        $days = floor($this->sla_hours / 24);
        $hours = $this->sla_hours % 24;

        if ($hours === 0) {
            return $days === 1 ? '1 day' : "{$days} days";
        }

        return $days === 1 ? "1 day {$hours}h" : "{$days} days {$hours}h";
    }

    /**
     * Get category statistics
     */
    public static function getStatistics(): array
    {
        $categories = static::active()->with([
            'tickets' => function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            },
        ])->get();

        return $categories->map(function ($category) {
            $tickets = $category->tickets;
            $openTickets = $tickets->whereNotIn('status', ['closed', 'cancelled']);
            $slaBreached = $tickets->filter(function ($ticket) use ($category) {
                return $category->isSlaBreached($ticket->created_at);
            });

            return [
                'key' => $category->key,
                'name' => $category->name,
                'color' => $category->color,
                'total_tickets' => $tickets->count(),
                'open_tickets' => $openTickets->count(),
                'sla_breached' => $slaBreached->count(),
                'average_resolution_hours' => self::calculateAverageResolutionTime($tickets),
            ];
        })->toArray();
    }

    /**
     * Calculate average resolution time for tickets
     */
    private static function calculateAverageResolutionTime($tickets): ?float
    {
        $resolvedTickets = $tickets->whereNotNull('resolved_at');

        if ($resolvedTickets->isEmpty()) {
            return null;
        }

        $totalHours = $resolvedTickets->sum(function ($ticket) {
            return $ticket->created_at->diffInHours($ticket->resolved_at);
        });

        return round($totalHours / $resolvedTickets->count(), 1);
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure only one default category
        static::saving(function ($category) {
            if ($category->is_default) {
                static::where('id', '!=', $category->id)
                    ->update(['is_default' => false]);
            }
        });

        // Prevent deletion of categories that are in use
        static::deleting(function ($category) {
            if ($category->tickets()->exists()) {
                throw new \Exception("Cannot delete category '{$category->name}' because it is being used by existing tickets.");
            }
        });
    }
}
