<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
        // Load accounts with hierarchy by default for better display
        $accounts = Account::query()
            ->with(['parent', 'children', 'users'])
            ->orderBy('parent_id', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // Build hierarchical structure for display
        $hierarchicalAccounts = $this->buildHierarchicalDisplayList($accounts);

        return response()->json([
            'data' => AccountResource::collection($hierarchicalAccounts),
            'meta' => [
                'total' => $accounts->count(),
                'hierarchical' => true
            ]
        ]);
    }

    /**
     * Get accounts for hierarchical selector (critical for domain mapping).
     */
    public function selector(Request $request): JsonResponse
    {
        // For now, return all accounts until permission system is fully implemented
        $accounts = Account::query()->get();

        // Load hierarchy for AccountSelector component
        $accounts->load(['parent', 'children']);

        // Build hierarchical structure for frontend
        $hierarchical = $this->buildHierarchicalAccounts($accounts);

        return response()->json([
            'data' => $hierarchical,
            'meta' => [
                'count' => $accounts->count(),
                'for_selector' => true
            ]
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
            'company_name' => 'nullable|string|max:255',
            'account_type' => 'required|in:customer,prospect,partner,internal',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:accounts,id',
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
            'is_active' => 'boolean'
        ]);
        
        $account = Account::create($validated);
        
        return response()->json([
            'message' => 'Account created successfully',
            'data' => new AccountResource($account)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Account $account): JsonResponse
    {
        $account->load(['parent', 'children', 'users']);
        
        return response()->json([
            'data' => new AccountResource($account)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'account_type' => 'required|in:customer,prospect,partner,internal',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:accounts,id',
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
            'is_active' => 'boolean'
        ]);
        
        $account->update($validated);
        
        return response()->json([
            'message' => 'Account updated successfully',
            'data' => new AccountResource($account)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account): JsonResponse
    {
        if ($account->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete account that has child accounts'
            ], 422);
        }
        
        if ($account->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete account that has assigned users'
            ], 422);
        }
        
        $account->delete();
        
        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }

    /**
     * Build hierarchical display list maintaining parent-child relationships.
     */
    protected function buildHierarchicalDisplayList($accounts): array
    {
        $accountsById = $accounts->keyBy('id');
        $displayList = [];
        
        // First, add all root accounts
        foreach ($accounts as $account) {
            if (!$account->parent_id) {
                $displayList = array_merge($displayList, $this->flattenAccountTree($account, $accountsById, 0));
            }
        }
        
        return $displayList;
    }
    
    /**
     * Recursively flatten account tree for hierarchical display.
     */
    protected function flattenAccountTree(Account $account, $accountsById, int $depth): array
    {
        $list = [$account];
        
        // Set hierarchy level for display
        $account->hierarchy_level = $depth;
        
        // Add children recursively
        foreach ($account->children as $child) {
            if ($accountsById->has($child->id)) {
                $list = array_merge($list, $this->flattenAccountTree($child, $accountsById, $depth + 1));
            }
        }
        
        return $list;
    }

    /**
     * Build hierarchical account structure for AccountSelector component.
     */
    protected function buildHierarchicalAccounts($accounts): array
    {
        $hierarchy = [];
        $accountsById = $accounts->keyBy('id');

        foreach ($accounts as $account) {
            if (!$account->parent_id) {
                // Root level account
                $hierarchy[] = $this->buildAccountTree($account, $accountsById, 0);
            }
        }

        return $hierarchy;
    }

    /**
     * Recursively build account tree with depth indicators.
     */
    protected function buildAccountTree(Account $account, $accountsById, int $depth): array
    {
        $node = [
            'id' => $account->id,
            'name' => $account->name,
            'parent_id' => $account->parent_id,
            'depth' => $depth,
            'has_children' => $account->children()->count() > 0,
            'children' => []
        ];

        // Add children recursively
        foreach ($account->children as $child) {
            if ($accountsById->has($child->id)) {
                $node['children'][] = $this->buildAccountTree($child, $accountsById, $depth + 1);
            }
        }

        return $node;
    }
}
