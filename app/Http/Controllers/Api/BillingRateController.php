<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BillingRate;
use App\Services\BillingRateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class BillingRateController extends Controller
{
    protected BillingRateService $billingRateService;
    
    public function __construct(BillingRateService $billingRateService)
    {
        $this->billingRateService = $billingRateService;
    }
    
    /**
     * Display a listing of billing rates
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // If account_id is provided, return hierarchical rates for that account
        if ($request->has('account_id') && $request->account_id) {
            $account = Account::findOrFail($request->account_id);
            
            // Get the standard available rates
            $rates = $this->billingRateService->getAvailableRatesForAccount($account);
            
            // If include_rate_id is specified, ensure that specific rate is included
            if ($request->has('include_rate_id') && $request->include_rate_id) {
                $specificRateId = $request->include_rate_id;
                
                // Check if the rate is already in the collection
                if (!$rates->where('id', $specificRateId)->count()) {
                    // Rate not in collection, try to find and add it
                    $specificRate = BillingRate::find($specificRateId);
                    if ($specificRate && $specificRate->is_active) {
                        // Mark it as a special inclusion
                        $specificRate->inheritance_source = $specificRate->account_id ? 'account' : 'global';
                        $specificRate->inherited_from_account = null;
                        $specificRate->inherited_from_account_id = null;
                        $specificRate->is_timer_specific = true; // Flag to indicate this was specifically requested
                        
                        // Add it to the collection
                        $rates->push($specificRate);
                        
                        // Re-sort the collection
                        $rates = $rates->sortBy(function ($rate) {
                            $priority = 0;
                            if ($rate->inheritance_source === 'account' && $rate->is_default) $priority = 1;
                            elseif ($rate->inheritance_source === 'account') $priority = 2;
                            elseif ($rate->inheritance_source === 'global' && $rate->is_default) $priority = 3;
                            else $priority = 4;
                            
                            return sprintf('%02d_%s', $priority, $rate->name);
                        })->values();
                    }
                }
            }
            
            return response()->json(['data' => $rates]);
        }
        
        // Otherwise return all rates (for settings page)
        $query = BillingRate::query();
        
        // Apply filters
        if ($request->has('is_template')) {
            $query->where('is_template', $request->boolean('is_template'));
        }
        
        // Only show rates user can access
        if (!$user->hasAnyPermission(['admin.read', 'billing.manage'])) {
            $query->where('is_active', true);
        }
        
        $rates = $query->with(['account', 'user'])
            ->orderBy('account_id', 'asc')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
        
        return response()->json(['data' => $rates]);
    }
    
    /**
     * Store a newly created billing rate
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyPermission(['billing.manage', 'admin.write'])) {
            return response()->json(['error' => 'Insufficient permissions.'], 403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:9999.99',
            'description' => 'nullable|string|max:1000',
            'account_id' => 'nullable|exists:accounts,id',
            'is_template' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean'
        ]);
        
        // Handle default rate logic
        if ($validated['is_default'] ?? false) {
            if ($validated['account_id']) {
                // Only one default rate per account
                BillingRate::where('account_id', $validated['account_id'])
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            } else {
                // Only one global default rate
                BillingRate::whereNull('account_id')
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        }
        
        $billingRate = BillingRate::create([
            'name' => $validated['name'],
            'rate' => $validated['rate'],
            'description' => $validated['description'] ?? null,
            'account_id' => $validated['account_id'] ?? null,
            'is_template' => $validated['is_template'] ?? false,
            'is_default' => $validated['is_default'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $user->id
        ]);
        
        // Clear relevant caches
        if ($validated['account_id']) {
            $account = Account::find($validated['account_id']);
            if ($account) {
                $this->billingRateService->clearCacheForAccount($account);
            }
        } else {
            $this->billingRateService->clearAllCaches();
        }
        
        return response()->json([
            'data' => $billingRate->load(['account', 'user']),
            'message' => 'Billing rate created successfully.'
        ], 201);
    }
    
    /**
     * Display the specified billing rate
     */
    public function show(Request $request, BillingRate $billingRate): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyPermission(['billing.view', 'billing.manage', 'admin.read'])) {
            return response()->json(['error' => 'Insufficient permissions.'], 403);
        }
        
        return response()->json(['data' => $billingRate]);
    }
    
    /**
     * Update the specified billing rate
     */
    public function update(Request $request, BillingRate $billingRate): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyPermission(['billing.manage', 'admin.write'])) {
            return response()->json(['error' => 'Insufficient permissions.'], 403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:9999.99',
            'description' => 'nullable|string|max:1000',
            'account_id' => 'nullable|exists:accounts,id',
            'is_template' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean'
        ]);
        
        // Handle default rate logic
        if ($validated['is_default'] ?? false) {
            if ($validated['account_id']) {
                // Only one default rate per account
                BillingRate::where('account_id', $validated['account_id'])
                    ->where('is_default', true)
                    ->where('id', '!=', $billingRate->id)
                    ->update(['is_default' => false]);
            } else {
                // Only one global default rate
                BillingRate::whereNull('account_id')
                    ->where('is_default', true)
                    ->where('id', '!=', $billingRate->id)
                    ->update(['is_default' => false]);
            }
        }
        
        // Store old account_id for cache clearing
        $oldAccountId = $billingRate->account_id;
        
        $billingRate->update($validated);
        
        // Clear relevant caches for both old and new accounts
        if ($oldAccountId) {
            $oldAccount = Account::find($oldAccountId);
            if ($oldAccount) {
                $this->billingRateService->clearCacheForAccount($oldAccount);
            }
        }
        
        if ($billingRate->account_id && $billingRate->account_id !== $oldAccountId) {
            $newAccount = Account::find($billingRate->account_id);
            if ($newAccount) {
                $this->billingRateService->clearCacheForAccount($newAccount);
            }
        }
        
        if (!$oldAccountId && !$billingRate->account_id) {
            $this->billingRateService->clearAllCaches();
        }
        
        return response()->json([
            'data' => $billingRate->load(['account', 'user']),
            'message' => 'Billing rate updated successfully.'
        ]);
    }
    
    /**
     * Remove the specified billing rate
     */
    public function destroy(Request $request, BillingRate $billingRate): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyPermission(['billing.manage', 'admin.write'])) {
            return response()->json(['error' => 'Insufficient permissions.'], 403);
        }
        
        // Check if rate is in use
        if ($billingRate->tickets()->count() > 0 || $billingRate->timeEntries()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete billing rate that is currently in use.'
            ], 422);
        }
        
        $billingRate->delete();
        
        return response()->json(['message' => 'Billing rate deleted successfully.']);
    }
}