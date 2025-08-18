<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Http\Resources\UserResource;
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
        $accounts = Account::query()
            ->with(['users'])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'data' => AccountResource::collection($accounts),
            'meta' => [
                'total' => $accounts->count()
            ]
        ]);
    }

    /**
     * Get users belonging to a specific account
     */
    public function users(Request $request, Account $account): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has permission to view this account's users
        if (!$user->hasAnyPermission(['admin.read', 'admin.write', 'accounts.manage', 'users.manage.account'])) {
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
            'meta' => $users->toArray()
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
        $account->load(['users']);
        
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

}
