<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingRate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class BillingRateController extends Controller
{
    /**
     * Display a listing of billing rates
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = BillingRate::query();
        
        // Apply filters
        if ($request->has('is_template')) {
            $query->where('is_template', $request->boolean('is_template'));
        }
        
        
        // Only show rates user can access
        if (!$user->hasAnyPermission(['admin.read', 'billing.manage'])) {
            $query->where('is_active', true);
        }
        
        $rates = $query->orderBy('name')->get();
        
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
            'is_template' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean'
        ]);
        
        // Only one default rate
        if ($validated['is_default'] ?? false) {
            BillingRate::where('is_default', true)
                ->update(['is_default' => false]);
        }
        
        $billingRate = BillingRate::create([
            'name' => $validated['name'],
            'rate' => $validated['rate'],
            'description' => $validated['description'] ?? null,
            'is_template' => $validated['is_template'] ?? false,
            'is_default' => $validated['is_default'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $user->id
        ]);
        
        return response()->json([
            'data' => $billingRate,
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
            'is_template' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean'
        ]);
        
        // Only one default rate
        if ($validated['is_default'] ?? false) {
            BillingRate::where('is_default', true)
                ->where('id', '!=', $billingRate->id)
                ->update(['is_default' => false]);
        }
        
        $billingRate->update($validated);
        
        return response()->json([
            'data' => $billingRate,
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