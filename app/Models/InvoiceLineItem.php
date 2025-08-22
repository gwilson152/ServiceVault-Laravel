<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'invoice_id',
        'time_entry_id',
        'ticket_addon_id',
        'line_type',
        'sort_order',
        'description',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'billable',
        'taxable',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billable' => 'boolean',
        'taxable' => 'boolean',
        'metadata' => 'array',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($lineItem) {
            $lineItem->calculateTotals();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class);
    }

    public function ticketAddon(): BelongsTo
    {
        return $this->belongsTo(TicketAddon::class);
    }

    public function calculateTotals(): void
    {
        $subtotal = $this->quantity * $this->unit_price;
        $afterDiscount = $subtotal - $this->discount_amount;

        // Calculate tax amount based on invoice tax settings and line item taxable status
        if ($this->invoice && $this->invoice->account_id) {
            $isItemTaxable = $this->invoice->isLineItemTaxable($this);

            if ($isItemTaxable) {
                $taxService = app(\App\Services\TaxService::class);
                $effectiveTaxRate = $this->invoice->override_tax
                    ? ($this->invoice->tax_rate ?? 0)
                    : $taxService->getEffectiveTaxRate($this->invoice->account_id);

                $this->tax_amount = round($afterDiscount * ($effectiveTaxRate / 100), 2);
            } else {
                $this->tax_amount = 0;
            }
        } else {
            // Fallback for cases where invoice is not loaded
            $this->tax_amount = $afterDiscount * (($this->tax_rate ?? 0) / 100);
        }

        $this->total_amount = $afterDiscount + $this->tax_amount;
    }

    public function getFormattedTotalAttribute(): string
    {
        return '$'.number_format($this->total_amount, 2);
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return '$'.number_format($this->unit_price, 2);
    }

    // Scopes
    public function scopeTimeEntries($query)
    {
        return $query->where('line_type', 'time_entry');
    }

    public function scopeAddons($query)
    {
        return $query->where('line_type', 'addon');
    }

    public function scopeSeparators($query)
    {
        return $query->where('line_type', 'separator');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeBillable($query)
    {
        return $query->where('billable', true);
    }

    // Helper methods
    public function isSeparator(): bool
    {
        return $this->line_type === 'separator';
    }
}
