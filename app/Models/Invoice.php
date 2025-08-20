<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'invoice_number',
        'account_id',
        'user_id',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_rate',
        'tax_application_mode',
        'override_tax',
        'tax_amount',
        'total',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'override_tax' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (! $invoice->invoice_number) {
                $invoice->invoice_number = static::generateInvoiceNumber($invoice->account_id);
            }
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function ticketAddons(): HasMany
    {
        return $this->hasMany(TicketAddon::class);
    }

    // Scopes
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', ['paid', 'cancelled']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'sent')
            ->where('due_date', '>=', now());
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // Helper methods
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && ! in_array($this->status, ['paid', 'cancelled']);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function getOutstandingBalance(): float
    {
        $totalPaid = $this->payments()->where('status', 'completed')->sum('amount');

        return $this->total - $totalPaid;
    }

    public function calculateTotals(): void
    {
        // First, recalculate each line item's tax amount
        foreach ($this->lineItems as $lineItem) {
            $lineItem->calculateTotals();
            $lineItem->save();
        }
        
        // Then calculate invoice totals from updated line items
        $this->subtotal = $this->lineItems->sum(function($item) {
            $subtotal = $item->quantity * $item->unit_price;
            return $subtotal - $item->discount_amount;
        });
        
        $this->tax_amount = $this->lineItems->sum('tax_amount');
        $this->total = $this->subtotal + $this->tax_amount;
    }

    /**
     * Calculate the taxable subtotal based on line item taxable settings
     */
    public function calculateTaxableSubtotal(): float
    {
        $taxableSubtotal = 0;
        $taxService = app(\App\Services\TaxService::class);
        
        // Get effective tax application mode
        $effectiveMode = $this->override_tax 
            ? ($this->tax_application_mode ?? 'all_items')
            : $taxService->getEffectiveTaxApplicationMode($this->account_id);
        
        foreach ($this->lineItems as $item) {
            $isItemTaxable = $this->isLineItemTaxable($item, $effectiveMode);
            
            if ($isItemTaxable) {
                $taxableSubtotal += $item->total_amount;
            }
        }
        
        return $taxableSubtotal;
    }

    /**
     * Determine if a line item is taxable based on its setting and inheritance
     */
    public function isLineItemTaxable(InvoiceLineItem $item, string $effectiveMode = null): bool
    {
        // Explicit taxable setting takes precedence
        if ($item->taxable !== null) {
            return $item->taxable;
        }
        
        $taxService = app(\App\Services\TaxService::class);
        
        // Inherit from tax application mode
        $mode = $effectiveMode ?? ($this->override_tax 
            ? ($this->tax_application_mode ?? 'all_items')
            : $taxService->getEffectiveTaxApplicationMode($this->account_id));
        
        switch ($mode) {
            case 'all_items':
                // For time entries, check the specific setting
                if ($item->line_type === 'time_entry') {
                    return $taxService->getTimeEntriesTaxableDefault($this->account_id);
                }
                return true;
            case 'non_service_items':
                return $item->line_type !== 'time_entry';
            case 'custom':
                return $item->taxable === true; // Only explicitly marked as taxable
            default:
                // For time entries, check the specific setting
                if ($item->line_type === 'time_entry') {
                    return $taxService->getTimeEntriesTaxableDefault($this->account_id);
                }
                return true;
        }
    }

    /**
     * Get inherited tax settings for this invoice
     */
    public function getInheritedTaxSettings(): array
    {
        return app(\App\Services\TaxService::class)->getAccountTaxSettings($this->account_id);
    }

    public static function generateInvoiceNumber(string $accountId): string
    {
        $settings = \App\Models\BillingSetting::where('account_id', $accountId)->first()
                   ?? \App\Models\BillingSetting::whereNull('account_id')->first();

        // If no settings exist, create default global settings
        if (!$settings) {
            $settings = \App\Models\BillingSetting::create([
                'account_id' => null, // Global settings
                'company_name' => 'Company Name',
                'company_address' => 'Company Address',
                'company_email' => 'billing@company.com',
                'invoice_prefix' => 'INV',
                'next_invoice_number' => 1001,
                'payment_terms' => 30,
                'currency' => 'USD',
                'timezone' => 'UTC',
                'date_format' => 'Y-m-d',
                'auto_send_invoices' => false,
                'send_payment_reminders' => true,
            ]);
        }

        $prefix = $settings->invoice_prefix ?? 'INV';
        $nextNumber = $settings->next_invoice_number ?? 1001;

        // Update the next number
        $settings->increment('next_invoice_number');

        return $prefix.'-'.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'sent' => $this->isOverdue() ? 'red' : 'blue',
            'paid' => 'green',
            'overdue' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->isOverdue() || ! $this->due_date) {
            return 0;
        }

        return $this->due_date->diffInDays(now());
    }
}
