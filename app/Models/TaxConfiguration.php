<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxConfiguration extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'account_id',
        'name',
        'jurisdiction',
        'tax_rate',
        'tax_type',
        'tax_number',
        'is_compound',
        'is_active',
        'applicable_categories',
        'effective_date',
        'expiry_date',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:4',
        'is_compound' => 'boolean',
        'is_active' => 'boolean',
        'applicable_categories' => 'array',
        'effective_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('effective_date', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('expiry_date')
                          ->orWhere('expiry_date', '>=', now());
                    });
    }

    public function scopeByJurisdiction($query, $jurisdiction)
    {
        return $query->where('jurisdiction', $jurisdiction);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where(function ($q) use ($category) {
            $q->whereNull('applicable_categories')
              ->orWhereJsonContains('applicable_categories', $category);
        });
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('account_id');
    }

    // Helper methods
    public function isEffective(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        if ($this->effective_date->isFuture()) {
            return false;
        }
        
        if ($this->expiry_date && $this->expiry_date->isPast()) {
            return false;
        }
        
        return true;
    }

    public function appliesToCategory($category): bool
    {
        if (!$this->applicable_categories) {
            return true; // Applies to all categories
        }
        
        return in_array($category, $this->applicable_categories);
    }

    public function calculateTax($amount): float
    {
        if (!$this->isEffective()) {
            return 0;
        }
        
        return round($amount * ($this->tax_rate / 100), 2);
    }

    public function getFormattedRateAttribute(): string
    {
        return number_format($this->tax_rate, 2) . '%';
    }

    public function getTaxTypeLabelAttribute(): string
    {
        return match($this->tax_type) {
            'sales' => 'Sales Tax',
            'vat' => 'VAT',
            'gst' => 'GST',
            'other' => 'Other Tax',
            default => ucfirst($this->tax_type),
        };
    }
}
