<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class BillingSchedule extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'account_id',
        'created_by_user_id',
        'name',
        'description',
        'frequency',
        'interval',
        'start_date',
        'end_date',
        'next_billing_date',
        'last_billed_date',
        'is_active',
        'auto_send',
        'payment_terms',
        'billing_items',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'last_billed_date' => 'date',
        'is_active' => 'boolean',
        'auto_send' => 'boolean',
        'billing_items' => 'array',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('next_billing_date', '<=', now())
                    ->where('is_active', true);
    }

    public function scopeByFrequency($query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    // Helper methods
    public function isDue(): bool
    {
        return $this->is_active && $this->next_billing_date->isPast();
    }

    public function calculateNextBillingDate(): Carbon
    {
        $current = $this->next_billing_date ?? $this->start_date;
        
        return match($this->frequency) {
            'weekly' => $current->addWeeks($this->interval),
            'monthly' => $current->addMonths($this->interval),
            'quarterly' => $current->addMonths($this->interval * 3),
            'annually' => $current->addYears($this->interval),
            default => $current->addMonths($this->interval),
        };
    }

    public function updateNextBillingDate(): bool
    {
        $this->last_billed_date = $this->next_billing_date;
        $this->next_billing_date = $this->calculateNextBillingDate();
        
        // Check if we've passed the end date
        if ($this->end_date && $this->next_billing_date->isAfter($this->end_date)) {
            $this->is_active = false;
        }
        
        return $this->save();
    }

    public function getFrequencyLabelAttribute(): string
    {
        $label = ucfirst($this->frequency);
        if ($this->interval > 1) {
            $label = "Every {$this->interval} " . str_plural($this->frequency, $this->interval);
        }
        return $label;
    }
}
