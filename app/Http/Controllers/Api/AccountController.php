<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Http\Resources\UserResource;
use App\Models\Account;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Display a listing of accounts accessible to the user.
     */
    public function index(Request $request): JsonResponse
    {
        $accounts = Account::query()
            ->with(['users'])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'data' => AccountResource::collection($accounts),
            'meta' => [
                'total' => $accounts->count(),
            ],
        ]);
    }

    /**
     * Get users belonging to a specific account
     */
    public function users(Request $request, Account $account): JsonResponse
    {
        $user = $request->user();

        // Check if user has permission to view this account's users
        if (! $user->hasAnyPermission(['admin.read', 'admin.write', 'accounts.manage', 'users.manage.account'])) {
            // If not admin, only allow if it's their own account
            if ($account->id !== $user->account_id) {
                return response()->json(['error' => 'Unauthorized access.'], 403);
            }
        }

        $roleContext = $request->input('role_context'); // 'account_user' for customers, 'service_provider' for agents

        $query = $account->users()->with(['roleTemplate']);

        // Filter by role context if specified
        if ($roleContext) {
            $query->whereHas('roleTemplate', function ($roleQuery) use ($roleContext) {
                $roleQuery->where('context', $roleContext);
            });
        }

        // Apply pagination
        $perPage = min($request->input('per_page', 15), 100);
        $users = $query->paginate($perPage);

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => $users->toArray(),
        ]);
    }

    /**
     * Get accounts for selector components.
     */
    public function selector(Request $request): JsonResponse
    {
        $accounts = Account::query()->get();

        return response()->json([
            'data' => $accounts,
            'meta' => [
                'count' => $accounts->count(),
                'for_selector' => true,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account_type' => 'required|in:customer,prospect,partner,internal',
            'description' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'billing_address' => 'nullable|string',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            // Tax configuration
            'override_tax' => 'nullable|boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_application_mode' => 'nullable|in:all_items,non_service_items,custom',
            'tax_exempt' => 'nullable|boolean',
        ]);

        // Create account without tax settings
        $accountData = collect($validated)->except(['override_tax', 'tax_rate', 'tax_application_mode', 'tax_exempt'])->toArray();
        $account = Account::create($accountData);

        // Handle tax settings via TaxService if override_tax is enabled
        if ($validated['override_tax'] ?? false) {
            $taxService = app(\App\Services\TaxService::class);
            $taxSettings = [];
            
            if (isset($validated['tax_rate'])) {
                $taxSettings['default_rate'] = $validated['tax_rate'];
            }
            if (isset($validated['tax_application_mode'])) {
                $taxSettings['default_application_mode'] = $validated['tax_application_mode'];
            }
            if (isset($validated['tax_exempt'])) {
                $taxSettings['exempt'] = $validated['tax_exempt'];
            }
            
            if (!empty($taxSettings)) {
                $taxService->setAccountTaxSettings($account->id, $taxSettings);
            }
        }

        return response()->json([
            'message' => 'Account created successfully',
            'data' => new AccountResource($account),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Account $account): JsonResponse
    {
        $account->load(['users']);

        return response()->json([
            'data' => new AccountResource($account),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account_type' => 'required|in:customer,prospect,partner,internal',
            'description' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'billing_address' => 'nullable|string',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_country' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            // Tax configuration
            'override_tax' => 'nullable|boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_application_mode' => 'nullable|in:all_items,non_service_items,custom',
            'tax_exempt' => 'nullable|boolean',
        ]);

        // Update account without tax settings
        $accountData = collect($validated)->except(['override_tax', 'tax_rate', 'tax_application_mode', 'tax_exempt'])->toArray();
        $account->update($accountData);

        // Handle tax settings via TaxService
        $taxService = app(\App\Services\TaxService::class);
        
        if ($validated['override_tax'] ?? false) {
            // Set account-specific tax settings
            $taxSettings = [];
            
            if (isset($validated['tax_rate'])) {
                $taxSettings['default_rate'] = $validated['tax_rate'];
            }
            if (isset($validated['tax_application_mode'])) {
                $taxSettings['default_application_mode'] = $validated['tax_application_mode'];
            }
            if (isset($validated['tax_exempt'])) {
                $taxSettings['exempt'] = $validated['tax_exempt'];
            }
            
            if (!empty($taxSettings)) {
                $taxService->setAccountTaxSettings($account->id, $taxSettings);
            }
        } else {
            // Remove account-specific tax overrides (fall back to system defaults)
            \App\Models\Setting::where('type', 'account')
                               ->where('account_id', $account->id)
                               ->whereIn('key', ['tax.default_rate', 'tax.default_application_mode', 'tax.exempt'])
                               ->delete();
        }

        return response()->json([
            'message' => 'Account updated successfully',
            'data' => new AccountResource($account),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account): JsonResponse
    {
        if ($account->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete account that has assigned users',
            ], 422);
        }

        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }
}
