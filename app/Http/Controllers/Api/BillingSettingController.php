<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingSetting;
use Illuminate\Http\Request;

class BillingSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get global settings and user's account settings
        $settings = collect();

        // Add global settings
        $globalSettings = BillingSetting::whereNull('account_id')->first();
        if ($globalSettings) {
            $settings->push($globalSettings);
        }

        // Add account-specific settings if user has access
        if ($user->hasAnyPermission(['billing.admin', 'admin.read'])) {
            // Admin can see all account settings
            $accountSettings = BillingSetting::whereNotNull('account_id')->get();
            $settings = $settings->merge($accountSettings);
        } else {
            // Regular user can only see their account settings
            $userAccountIds = $user->accounts()->pluck('accounts.id');
            $accountSettings = BillingSetting::whereIn('account_id', $userAccountIds)->get();
            $settings = $settings->merge($accountSettings);
        }

        return response()->json([
            'data' => $settings,
            'message' => 'Billing settings retrieved successfully',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Check permissions
        if (! $user->hasAnyPermission(['billing.admin', 'billing.manage', 'admin.write'])) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        $validated = $request->validate([
            'account_id' => 'nullable|exists:accounts,id',
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'required|email|max:255',
            'company_website' => 'nullable|url|max:255',
            'tax_id' => 'nullable|string|max:100',
            'invoice_prefix' => 'required|string|max:10',
            'next_invoice_number' => 'required|integer|min:1',
            'payment_terms' => 'required|integer|min:1|max:365',
            'currency' => 'required|string|size:3',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'payment_methods' => 'array',
            'payment_methods.*' => 'string|in:bank_transfer,check,credit_card,paypal',
            'auto_send_invoices' => 'boolean',
            'send_payment_reminders' => 'boolean',
            'reminder_schedule' => 'array',
            'reminder_schedule.*' => 'integer',
            'invoice_footer' => 'nullable|string',
            'payment_instructions' => 'nullable|string',
        ]);

        $billingSetting = BillingSetting::create($validated);

        return response()->json([
            'data' => $billingSetting,
            'message' => 'Billing settings created successfully',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, BillingSetting $billingSetting)
    {
        $user = $request->user();

        // Check if user can access this setting
        if ($billingSetting->account_id && ! $user->hasAnyPermission(['billing.admin', 'admin.read'])) {
            $userAccountIds = $user->accounts()->pluck('accounts.id');
            if (! $userAccountIds->contains($billingSetting->account_id)) {
                return response()->json(['error' => 'Access denied'], 403);
            }
        }

        return response()->json([
            'data' => $billingSetting,
            'message' => 'Billing setting retrieved successfully',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BillingSetting $billingSetting)
    {
        $user = $request->user();

        // Check permissions
        if (! $user->hasAnyPermission(['billing.admin', 'billing.manage', 'admin.write'])) {
            // Check if user can manage their own account settings
            if ($billingSetting->account_id) {
                $userAccountIds = $user->accounts()->pluck('accounts.id');
                if (! $userAccountIds->contains($billingSetting->account_id)) {
                    return response()->json(['error' => 'Insufficient permissions'], 403);
                }
            } else {
                return response()->json(['error' => 'Insufficient permissions'], 403);
            }
        }

        $validated = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'sometimes|email|max:255',
            'company_website' => 'nullable|url|max:255',
            'tax_id' => 'nullable|string|max:100',
            'invoice_prefix' => 'sometimes|string|max:10',
            'next_invoice_number' => 'sometimes|integer|min:1',
            'payment_terms' => 'sometimes|integer|min:1|max:365',
            'currency' => 'sometimes|string|size:3',
            'timezone' => 'sometimes|string|max:50',
            'date_format' => 'sometimes|string|max:20',
            'payment_methods' => 'sometimes|array',
            'payment_methods.*' => 'string|in:bank_transfer,check,credit_card,paypal',
            'auto_send_invoices' => 'sometimes|boolean',
            'send_payment_reminders' => 'sometimes|boolean',
            'reminder_schedule' => 'sometimes|array',
            'reminder_schedule.*' => 'integer',
            'invoice_footer' => 'nullable|string',
            'payment_instructions' => 'nullable|string',
        ]);

        $billingSetting->update($validated);

        return response()->json([
            'data' => $billingSetting,
            'message' => 'Billing settings updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, BillingSetting $billingSetting)
    {
        $user = $request->user();

        // Only admins can delete billing settings
        if (! $user->hasAnyPermission(['billing.admin', 'admin.write'])) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }

        // Don't allow deletion of global settings if it's the only one
        if (! $billingSetting->account_id && BillingSetting::whereNull('account_id')->count() <= 1) {
            return response()->json(['error' => 'Cannot delete the only global billing setting'], 422);
        }

        $billingSetting->delete();

        return response()->json([
            'message' => 'Billing setting deleted successfully',
        ]);
    }
}
