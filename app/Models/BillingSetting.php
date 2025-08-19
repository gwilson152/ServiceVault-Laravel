<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingSetting extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'account_id',
        'company_name',
        'company_address',
        'company_phone',
        'company_email',
        'company_website',
        'tax_id',
        'invoice_prefix',
        'next_invoice_number',
        'payment_terms',
        'currency',
        'timezone',
        'date_format',
        'payment_methods',
        'auto_send_invoices',
        'send_payment_reminders',
        'reminder_schedule',
        'invoice_footer',
        'payment_instructions',
        'metadata',
    ];

    protected $casts = [
        'payment_methods' => 'array',
        'auto_send_invoices' => 'boolean',
        'send_payment_reminders' => 'boolean',
        'reminder_schedule' => 'array',
        'metadata' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // Scopes
    public function scopeGlobal($query)
    {
        return $query->whereNull('account_id');
    }

    public function scopeForAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    // Helper methods
    public static function getForAccount($accountId = null)
    {
        // Try to get account-specific settings first
        if ($accountId) {
            $settings = static::where('account_id', $accountId)->first();
            if ($settings) {
                return $settings;
            }
        }

        // Fall back to global settings
        return static::whereNull('account_id')->first();
    }

    public function getNextInvoiceNumber(): string
    {
        $current = $this->next_invoice_number;
        $this->increment('next_invoice_number');

        return $this->invoice_prefix.'-'.str_pad($current, 4, '0', STR_PAD_LEFT);
    }

    public function getEnabledPaymentMethods(): array
    {
        return $this->payment_methods ?? ['bank_transfer'];
    }

    public function isPaymentMethodEnabled($method): bool
    {
        return in_array($method, $this->getEnabledPaymentMethods());
    }

    public function getReminderDays(): array
    {
        return $this->reminder_schedule ?? [7, 3, 0, -3, -7]; // Days relative to due date
    }

    public function getFormattedAddress(): string
    {
        return str_replace('\n', '<br>', $this->company_address);
    }

    public function getFormattedPaymentTerms(): string
    {
        return "Net {$this->payment_terms} days";
    }

    // Default settings factory
    public static function createDefault($accountId = null): self
    {
        return static::create([
            'account_id' => $accountId,
            'company_name' => 'Your Company Name',
            'company_address' => '123 Business St\nCity, State 12345',
            'company_email' => 'billing@yourcompany.com',
            'invoice_prefix' => 'INV',
            'next_invoice_number' => 1001,
            'payment_terms' => 30,
            'currency' => 'USD',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'payment_methods' => ['bank_transfer', 'check'],
            'auto_send_invoices' => false,
            'send_payment_reminders' => true,
            'reminder_schedule' => [7, 3, 0, -3, -7],
            'invoice_footer' => 'Thank you for your business!',
            'payment_instructions' => 'Please remit payment within the specified terms.',
        ]);
    }
}
