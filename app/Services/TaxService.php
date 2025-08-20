<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\Setting;

class TaxService
{
    /**
     * Get effective tax rate using hierarchy: Invoice → Account → System Default
     */
    public function getEffectiveTaxRate(?string $accountId = null, ?float $invoiceTaxRate = null): float
    {
        // 1. Invoice-specific tax rate (highest priority)
        if ($invoiceTaxRate !== null) {
            return $invoiceTaxRate;
        }

        // 2. Account-specific tax rate override
        if ($accountId) {
            $accountTaxRate = Setting::where('type', 'account')
                ->where('account_id', $accountId)
                ->where('key', 'tax.default_rate')
                ->first();
            
            if ($accountTaxRate && $accountTaxRate->value !== null) {
                return (float) $accountTaxRate->value;
            }
        }

        // 3. System default tax rate (fallback)
        $systemTaxRate = Setting::where('type', 'system')
            ->where('key', 'tax.default_rate')
            ->first();

        return $systemTaxRate ? (float) $systemTaxRate->value : 0.0;
    }

    /**
     * Get effective tax application mode using hierarchy: Invoice → Account → System Default
     */
    public function getEffectiveTaxApplicationMode(?string $accountId = null, ?string $invoiceTaxApplicationMode = null): string
    {
        // 1. Invoice-specific tax application mode (highest priority)
        if ($invoiceTaxApplicationMode) {
            return $invoiceTaxApplicationMode;
        }

        // 2. Account-specific tax application mode override
        if ($accountId) {
            $accountTaxMode = Setting::where('type', 'account')
                ->where('account_id', $accountId)
                ->where('key', 'tax.default_application_mode')
                ->first();
            
            if ($accountTaxMode && $accountTaxMode->value) {
                return $accountTaxMode->value;
            }
        }

        // 3. System default tax application mode (fallback)
        $systemTaxMode = Setting::where('type', 'system')
            ->where('key', 'tax.default_application_mode')
            ->first();

        return $systemTaxMode ? $systemTaxMode->value : 'all_items';
    }

    /**
     * Check if tax system is enabled system-wide
     */
    public function isTaxEnabled(): bool
    {
        $taxEnabled = Setting::where('type', 'system')
            ->where('key', 'tax.enabled')
            ->first();

        return $taxEnabled ? (bool) $taxEnabled->value : true; // Default enabled
    }

    /**
     * Check if account is tax exempt
     */
    public function isAccountTaxExempt(?string $accountId = null): bool
    {
        if (!$accountId) {
            return false;
        }

        $taxExempt = Setting::where('type', 'account')
            ->where('account_id', $accountId)
            ->where('key', 'tax.exempt')
            ->first();

        return $taxExempt ? (bool) $taxExempt->value : false;
    }

    /**
     * Get all tax settings for an account (includes inherited system defaults)
     */
    public function getAccountTaxSettings(?string $accountId = null): array
    {
        return [
            'tax_rate' => $this->getEffectiveTaxRate($accountId),
            'tax_application_mode' => $this->getEffectiveTaxApplicationMode($accountId),
            'tax_enabled' => $this->isTaxEnabled(),
            'tax_exempt' => $this->isAccountTaxExempt($accountId),
            'time_entries_taxable_by_default' => $this->getTimeEntriesTaxableDefault($accountId),
        ];
    }

    /**
     * Get whether time entries are taxable by default
     */
    public function getTimeEntriesTaxableDefault(?string $accountId = null): bool
    {
        // 1. Account-specific setting
        if ($accountId) {
            $accountSetting = Setting::where('type', 'account')
                ->where('account_id', $accountId)
                ->where('key', 'tax.time_entries_taxable_by_default')
                ->first();
            
            if ($accountSetting && $accountSetting->value !== null) {
                return (bool) $accountSetting->value;
            }
        }

        // 2. System default setting
        $systemSetting = Setting::where('type', 'system')
            ->where('key', 'tax.time_entries_taxable_by_default')
            ->first();

        return $systemSetting ? (bool) $systemSetting->value : true; // Default to taxable
    }

    /**
     * Set system default tax settings
     */
    public function setSystemTaxSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                [
                    'key' => "tax.{$key}",
                    'type' => 'system',
                    'account_id' => null,
                    'user_id' => null,
                ],
                ['value' => $value]
            );
        }
    }

    /**
     * Set account-specific tax settings
     */
    public function setAccountTaxSettings(string $accountId, array $settings): void
    {
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                [
                    'key' => "tax.{$key}",
                    'type' => 'account',
                    'account_id' => $accountId,
                    'user_id' => null,
                ],
                ['value' => $value]
            );
        }
    }

    /**
     * Calculate tax amount based on subtotal and tax settings
     */
    public function calculateTaxAmount(
        float $subtotal,
        float $taxableSubtotal,
        string $accountId = null,
        float $invoiceTaxRate = null,
        string $invoiceTaxApplicationMode = null
    ): float {
        // Check if tax system is enabled and account is not exempt
        if (!$this->isTaxEnabled() || $this->isAccountTaxExempt($accountId)) {
            return 0.0;
        }

        $effectiveTaxRate = $this->getEffectiveTaxRate($accountId, $invoiceTaxRate);
        
        if ($effectiveTaxRate <= 0) {
            return 0.0;
        }

        // Use taxable subtotal (which respects tax application mode)
        return round($taxableSubtotal * ($effectiveTaxRate / 100), 2);
    }

    /**
     * Get formatted tax rate as percentage string
     */
    public function getFormattedTaxRate(?string $accountId = null, ?float $invoiceTaxRate = null): string
    {
        $rate = $this->getEffectiveTaxRate($accountId, $invoiceTaxRate);
        return number_format($rate, 2) . '%';
    }

    /**
     * Get system tax defaults for new accounts/invoices
     */
    public function getSystemDefaults(): array
    {
        return [
            'default_rate' => (float) Setting::getValue('tax.default_rate', 0),
            'default_application_mode' => Setting::getValue('tax.default_application_mode', 'all_items'),
            'enabled' => (bool) Setting::getValue('tax.enabled', true),
        ];
    }
}