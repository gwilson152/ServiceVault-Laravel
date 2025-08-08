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
        $user = $request->user();

        // Get accounts accessible to user based on permissions
        $accounts = $this->permissionService->getAccessibleAccounts($user, 'accounts.view');

        // Support hierarchical loading with parent/children relationships
        if ($request->boolean('with_hierarchy')) {
            $accounts->load(['parent', 'children']);
        }

        return response()->json([
            'data' => AccountResource::collection($accounts),
            'meta' => [
                'total' => $accounts->count(),
                'hierarchical' => $request->boolean('with_hierarchy')
            ]
        ]);
    }

    /**
     * Get accounts for hierarchical selector (critical for domain mapping).
     */
    public function selector(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get accounts user can assign domain mappings to
        $accounts = $this->permissionService->getAccessibleAccounts($user, 'accounts.manage');

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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Account $account)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Account $account)
    {
        //
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
