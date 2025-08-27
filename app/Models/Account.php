<?php

namespace App\Models;

use App\Services\BillingRateService;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'account_type',
        'description',
        'external_id',
        'contact_person',
        'email',
        'phone',
        'website',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'tax_id',
        'notes',
        'settings',
        'theme_settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'theme_settings' => 'array',
        'is_active' => 'boolean',
        'account_type' => 'string',
    ];

    // User relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    // Billing relationships
    public function billingRates()
    {
        return $this->hasMany(BillingRate::class);
    }

    /**
     * Get inherited billing rates using the BillingRateService.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInheritedBillingRates()
    {
        return app(BillingRateService::class)->getAvailableRatesForAccount($this);
    }

    /**
     * Get the effective default billing rate for this account.
     */
    public function getEffectiveDefaultRate(): ?BillingRate
    {
        return app(BillingRateService::class)->getEffectiveRateForAccount($this);
    }

    // Business information accessors
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return empty($parts) ? null : implode(', ', $parts);
    }

    public function getFullBillingAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->billing_address,
            $this->billing_city,
            $this->billing_state,
            $this->billing_postal_code,
            $this->billing_country,
        ]);

        return empty($parts) ? null : implode(', ', $parts);
    }

    public function getAccountTypeDisplayAttribute(): string
    {
        $types = [
            'customer' => 'Customer',
            'prospect' => 'Prospect',
            'partner' => 'Partner',
            'internal' => 'Internal',
        ];

        return $types[$this->account_type] ?? ucfirst($this->account_type);
    }

    // Scopes for business queries
    public function scopeByType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Tax settings helper methods using TaxService
    public function getTaxSettings(): array
    {
        return app(\App\Services\TaxService::class)->getAccountTaxSettings($this->id);
    }

    public function getEffectiveTaxRate(): float
    {
        return app(\App\Services\TaxService::class)->getEffectiveTaxRate($this->id);
    }

    public function getEffectiveTaxApplicationMode(): string
    {
        return app(\App\Services\TaxService::class)->getEffectiveTaxApplicationMode($this->id);
    }

    public function isTaxExempt(): bool
    {
        return app(\App\Services\TaxService::class)->isAccountTaxExempt($this->id);
    }

    public function hasTaxOverrides(): bool
    {
        return Setting::where('type', 'account')
            ->where('account_id', $this->id)
            ->whereIn('key', ['tax.default_rate', 'tax.default_application_mode', 'tax.exempt'])
            ->exists();
    }
}
