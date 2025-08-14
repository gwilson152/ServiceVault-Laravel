<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Account;
use App\Models\RoleTemplate;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get users that can be assigned to tickets
     */
    public function assignableUsers(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check if user has permission to assign tickets
        if (!$user->hasAnyPermission(['tickets.assign', 'admin.write'])) {
            return response()->json([
                'message' => 'Insufficient permissions to view assignable users'
            ], 403);
        }
        
        // Get all active users for now (simplified to avoid role template complexity)
        $assignableUsers = User::select('id', 'name', 'email')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'data' => $assignableUsers,
            'message' => 'Assignable users retrieved successfully'
        ]);
    }

    /**
     * Display a listing of users with comprehensive filtering and search.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check permission to view users
        if (!$user->hasAnyPermission(['admin.read', 'users.view', 'users.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to view users'
            ], 403);
        }
        
        $query = User::with(['account', 'roleTemplate']);
        
        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        // Filter by role template
        if ($request->filled('role_template_id')) {
            $query->where('role_template_id', $request->role_template_id);
        }
        
        // Filter by account
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }
        
        // Sorting
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);
        
        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'total' => $users->total(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage()
            ]
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Check permission to create users
        if (!$user->hasAnyPermission(['admin.write', 'users.create', 'users.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to create users'
            ], 403);
        }
        
        // Determine if password is required based on invitation and active status
        $passwordRequired = !$request->boolean('send_invitation') && $request->boolean('is_active', true);
        
        // Determine if email is required based on active status
        $emailRequired = $request->boolean('is_active', true);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => $emailRequired ? 'required|email|unique:users,email' : 'nullable|email|unique:users,email',
            'password' => $passwordRequired ? 'required|string|min:8|confirmed' : 'nullable|string|min:8|confirmed',
            'timezone' => 'nullable|string|max:50',
            'locale' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
            'account_id' => 'nullable|exists:accounts,id',
            'role_template_id' => 'nullable|exists:role_templates,id',
            'preferences' => 'nullable|array',
            'send_invitation' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Create user
        $userData = $validator->validated();
        
        // Handle password - only hash if provided
        if (!empty($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        } else {
            // Remove password field if empty (for invitations/inactive users)
            unset($userData['password']);
        }
        
        // Set email verification based on whether invitation is sent
        if ($request->boolean('send_invitation')) {
            // Don't verify email if sending invitation - user will verify through invitation
            unset($userData['email_verified_at']);
        } else {
            $userData['email_verified_at'] = now();
        }
        
        // Remove invitation flag from stored data
        unset($userData['send_invitation']);
        
        $newUser = User::create($userData);
        
        // Assign account (single relationship)
        if ($request->filled('account_id')) {
            $newUser->account_id = $request->account_id;
            $newUser->save();
        }
        
        // Assign role template (single relationship)
        if ($request->filled('role_template_id')) {
            $newUser->role_template_id = $request->role_template_id;
            $newUser->save();
        }
        
        // Load relationships for response
        $newUser->load(['account', 'roleTemplate']);
        
        return response()->json([
            'message' => 'User created successfully',
            'data' => new UserResource($newUser)
        ], 201);
    }

    /**
     * Display the specified user with comprehensive details.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $requestingUser = $request->user();
        
        // Check permission to view user details
        if (!$requestingUser->hasAnyPermission(['admin.read', 'users.view', 'users.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to view user details'
            ], 403);
        }
        
        // Load comprehensive relationships
        $user->load([
            'account' => function ($query) {
                $query->withCount('users');
            },
            'roleTemplate',
            'timers' => function ($query) {
                $query->latest()->limit(5);
            },
            'timeEntries' => function ($query) {
                $query->latest()->limit(10);
            }
        ]);
        
        return response()->json([
            'data' => new UserResource($user)
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $requestingUser = $request->user();
        
        // Check permission to update users
        if (!$requestingUser->hasAnyPermission(['admin.write', 'users.edit', 'users.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to update users'
            ], 403);
        }
        
        // Determine if email is required based on active status
        $emailRequired = $request->boolean('is_active', $user->is_active);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => $emailRequired 
                ? ['required', 'email', Rule::unique('users')->ignore($user->id)]
                : ['nullable', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'timezone' => 'nullable|string|max:50',
            'locale' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
            'account_id' => 'nullable|exists:accounts,id',
            'role_template_id' => 'nullable|exists:role_templates,id',
            'preferences' => 'nullable|array'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Update user data
        $userData = $validator->validated();
        if (isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        } else {
            unset($userData['password']);
        }
        
        $user->update($userData);
        
        // Update account assignment (single relationship)
        if ($request->has('account_id')) {
            $user->account_id = $request->account_id;
        }
        
        // Update role template assignment (single relationship)
        if ($request->has('role_template_id')) {
            $user->role_template_id = $request->role_template_id;
        }
        
        $user->save();
        
        // Load relationships for response
        $user->load(['account', 'roleTemplate']);
        
        return response()->json([
            'message' => 'User updated successfully',
            'data' => new UserResource($user)
        ]);
    }

    /**
     * Remove (deactivate) the specified user.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $requestingUser = $request->user();
        
        // Check permission to delete users
        if (!$requestingUser->hasAnyPermission(['admin.write', 'users.delete', 'users.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to delete users'
            ], 403);
        }
        
        // Prevent self-deletion
        if ($user->id === $requestingUser->id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 422);
        }
        
        // Soft deactivation instead of hard delete
        $user->update(['is_active' => false]);
        
        return response()->json([
            'message' => 'User deactivated successfully'
        ]);
    }
    
    /**
     * Get user's service tickets (as assigned agent)
     */
    public function tickets(Request $request, User $user): JsonResponse
    {
        $requestingUser = $request->user();
        
        if (!$requestingUser->hasAnyPermission(['admin.read', 'users.view', 'tickets.view'])) {
            return response()->json(['message' => 'Insufficient permissions'], 403);
        }
        
        // Get tickets assigned to this user
        $tickets = $user->assignedTickets()
            ->with(['account', 'createdBy', 'timers', 'timeEntries'])
            ->latest()
            ->paginate(15);
            
        return response()->json([
            'data' => $tickets->items(),
            'meta' => [
                'total' => $tickets->total(),
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage()
            ]
        ]);
    }
    
    /**
     * Get user's time entries
     */
    public function timeEntries(Request $request, User $user): JsonResponse
    {
        $requestingUser = $request->user();
        
        if (!$requestingUser->hasAnyPermission(['admin.read', 'users.view', 'time.view'])) {
            return response()->json(['message' => 'Insufficient permissions'], 403);
        }
        
        $timeEntries = $user->timeEntries()
            ->with(['project', 'task', 'ticket', 'billingRate', 'approvedBy'])
            ->latest()
            ->paginate(15);
            
        return response()->json([
            'data' => $timeEntries->items(),
            'meta' => [
                'total' => $timeEntries->total(),
                'current_page' => $timeEntries->currentPage(),
                'last_page' => $timeEntries->lastPage()
            ]
        ]);
    }
    
    /**
     * Get user activity and analytics
     */
    public function activity(Request $request, User $user): JsonResponse
    {
        $requestingUser = $request->user();
        
        if (!$requestingUser->hasAnyPermission(['admin.read', 'users.view'])) {
            return response()->json(['message' => 'Insufficient permissions'], 403);
        }
        
        $activity = [
            'recent_logins' => [
                'last_login_at' => $user->last_login_at,
                'last_active_at' => $user->last_active_at,
            ],
            'statistics' => [
                'total_tickets_assigned' => $user->assignedTickets()->count(),
                'total_time_entries' => $user->timeEntries()->count(),
                'total_time_logged' => $user->timeEntries()->sum('duration'),
                'active_timers' => $user->timers()->where('status', 'running')->count(),
            ],
            'recent_activity' => [
                'recent_tickets' => $user->assignedTickets()->latest()->limit(5)->get(),
                'recent_time_entries' => $user->timeEntries()->latest()->limit(5)->get(),
                'recent_timers' => $user->timers()->latest()->limit(5)->get(),
            ]
        ];
        
        return response()->json(['data' => $activity]);
    }
    
    /**
     * Get user's assigned accounts with role context
     */
    public function accounts(Request $request, User $user): JsonResponse
    {
        $requestingUser = $request->user();
        
        if (!$requestingUser->hasAnyPermission(['admin.read', 'users.view', 'accounts.view'])) {
            return response()->json(['message' => 'Insufficient permissions'], 403);
        }
        
        // Since user has single account relationship, return it as array for compatibility
        $accounts = $user->account ? [$user->account] : [];
        
        if ($user->account) {
            $user->account->loadCount('users');
        }
            
        return response()->json(['data' => $accounts]);
    }
}
