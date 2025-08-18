<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BillingRate;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BillingRateService
{
    /**
     * Get the effective billing rate for an account.
     * 
     * Priority: Account-specific → Global default → First active
     * 
     * @param Account $account
     * @param string|null $rateType Optional rate type filter
     * @return BillingRate|null
     */
    public function getEffectiveRateForAccount(Account $account, ?string $rateType = null): ?BillingRate
    {
        // 1. Check for account-specific default rate
        $accountRate = $this->getAccountSpecificDefaultRate($account, $rateType);
        if ($accountRate) {
            $accountRate->inheritance_source = 'account';
            $accountRate->inherited_from_account = null;
            $accountRate->inherited_from_account_id = null;
            return $accountRate;
        }
        
        // 2. Fall back to global default rate
        $globalRate = $this->getGlobalDefaultRate($rateType);
        if ($globalRate) {
            $globalRate->inheritance_source = 'global';
            $globalRate->inherited_from_account = null;
            $globalRate->inherited_from_account_id = null;
            return $globalRate;
        }
        
        // 3. Last resort: first active rate
        return $this->getFirstActiveRate($rateType);
    }
    
    /**
     * Get all available billing rates for an account.
     * 
     * @param Account $account
     * @return Collection<BillingRate>
     */
    public function getAvailableRatesForAccount(Account $account): Collection
    {
        $rates = collect();
        
        // 1. Get account-specific rates
        $accountRates = BillingRate::where('account_id', $account->id)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
            
        foreach ($accountRates as $rate) {
            $rate->inheritance_source = 'account';
            $rate->inherited_from_account = null;
            $rate->inherited_from_account_id = null;
            $rates->push($rate);
        }
        
        // 2. Get global rates
        $globalRates = BillingRate::whereNull('account_id')
            ->whereNull('user_id')
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
            
        foreach ($globalRates as $rate) {
            // Only add if we don't already have a rate with the same name
            if (!$rates->where('name', $rate->name)->count()) {
                $rate->inheritance_source = 'global';
                $rate->inherited_from_account = null;
                $rate->inherited_from_account_id = null;
                $rates->push($rate);
            }
        }
        
        return $rates->sortBy(function ($rate) {
            // Sort by: account-specific defaults first, then global, then by name
            $priority = 0;
            if ($rate->inheritance_source === 'account' && $rate->is_default) $priority = 1;
            elseif ($rate->inheritance_source === 'account') $priority = 2;
            elseif ($rate->inheritance_source === 'global' && $rate->is_default) $priority = 3;
            else $priority = 4;
            
            return sprintf('%02d_%s', $priority, $rate->name);
        })->values();
    }
    
    /**
     * Get the default billing rate for an account (the one that should be pre-selected).
     * 
     * @param Account $account
     * @return BillingRate|null
     */
    public function getDefaultRateForAccount(Account $account): ?BillingRate
    {
        return $this->getEffectiveRateForAccount($account);
    }
    
    /**
     * Clear cached billing rates for an account.
     * Call this when billing rates are modified.
     * 
     * @param Account $account
     * @return void
     */
    public function clearCacheForAccount(Account $account): void
    {
        // Clear cache for this account
        Cache::forget("billing_rate_effective_{$account->id}");
        Cache::forget("billing_rates_available_{$account->id}");
    }
    
    /**
     * Clear all billing rate caches (useful when global rates change).
     * 
     * @return void
     */
    public function clearAllCaches(): void
    {
        // Clear all cached billing rates
        $pattern = 'billing_rate*';
        $keys = Cache::getRedis()->keys($pattern);
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }
    }
    
    /**
     * Get account-specific default rate.
     * 
     * @param Account $account
     * @param string|null $rateType
     * @return BillingRate|null
     */
    private function getAccountSpecificDefaultRate(Account $account, ?string $rateType = null): ?BillingRate
    {
        $query = BillingRate::where('account_id', $account->id)
            ->where('is_active', true)
            ->where('is_default', true);
            
        if ($rateType) {
            $query->where('rate_type', $rateType);
        }
        
        return $query->first();
    }
    
    /**
     * Get global default rate.
     * 
     * @param string|null $rateType
     * @return BillingRate|null
     */
    private function getGlobalDefaultRate(?string $rateType = null): ?BillingRate
    {
        $query = BillingRate::whereNull('account_id')
            ->whereNull('user_id')
            ->where('is_active', true)
            ->where('is_default', true);
            
        if ($rateType) {
            $query->where('rate_type', $rateType);
        }
        
        return $query->first();
    }
    
    /**
     * Get first active rate as last resort.
     * 
     * @param string|null $rateType
     * @return BillingRate|null
     */
    private function getFirstActiveRate(?string $rateType = null): ?BillingRate
    {
        $query = BillingRate::where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name');
            
        if ($rateType) {
            $query->where('rate_type', $rateType);
        }
        
        $rate = $query->first();
        if ($rate) {
            $rate->inheritance_source = 'fallback';
            $rate->inherited_from_account = null;
            $rate->inherited_from_account_id = null;
        }
        
        return $rate;
    }
    
}