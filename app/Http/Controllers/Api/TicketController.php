<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Account;
use App\Models\User;
use App\Models\BillingRate;
use App\Http\Resources\TicketResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;

class TicketController extends Controller
{
    /**
     * Display a listing of tickets with filters and pagination (API)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Build base query with user's accessible accounts
        $query = Ticket::with([
            'account:id,name', 
            'createdBy:id,name', 
            'assignedTo:id,name', 
            'billingRate:id,rate,currency'
        ]);
        
        // Apply user scope - employees see assigned tickets, managers/admins see team tickets
        if ($user->isSuperAdmin() || $user->hasAnyPermission(['admin.read', 'admin.write'])) {
            // Admins see all tickets in their accounts
            $accountIds = $user->accounts()->pluck('accounts.id');
            $query->whereIn('account_id', $accountIds);
        } elseif ($user->hasAnyPermission(['teams.manage', 'tickets.view.all'])) {
            // Managers see tickets in accounts they manage
            $managedAccountIds = $user->accounts()
                ->whereHas('users', function ($userQuery) use ($user) {
                    $userQuery->where('users.id', $user->id)
                             ->whereHas('roleTemplates', function ($roleQuery) {
                                 $roleQuery->whereJsonContains('permissions', 'teams.manage');
                             });
                })
                ->pluck('accounts.id');
            $query->whereIn('account_id', $managedAccountIds);
        } else {
            // Employees see tickets they created or are assigned to
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('assigned_to_id', $user->id)
                  ->orWhereHas('assignedUsers', function ($assignedQuery) use ($user) {
                      $assignedQuery->where('users.id', $user->id);
                  });
            });
        }
        
        // Apply filters
        $query->when($request->status, function ($q, $status) {
            if (is_array($status)) {
                $q->whereIn('status', $status);
            } else {
                $q->where('status', $status);
            }
        })->when($request->priority, function ($q, $priority) {
            $q->where('priority', $priority);
        })->when($request->category, function ($q, $category) {
            $q->where('category', $category);
        })->when($request->assigned_to, function ($q, $assignedTo) {
            if ($assignedTo === 'mine') {
                $q->where('assigned_to_id', $user->id);
            } elseif ($assignedTo === 'unassigned') {
                $q->whereNull('assigned_to_id');
            } else {
                $q->where('assigned_to_id', $assignedTo);
            }
        })->when($request->account_id, function ($q, $accountId) {
            $q->where('account_id', $accountId);
        })->when($request->overdue, function ($q) {
            $q->where('due_date', '<', now())->whereNotIn('status', ['closed', 'cancelled']);
        })->when($request->search, function ($q, $search) {
            $q->where(function ($searchQuery) use ($search) {
                $searchQuery->where('title', 'like', "%{$search}%")
                           ->orWhere('description', 'like', "%{$search}%")
                           ->orWhere('ticket_number', 'like', "%{$search}%");
            });
        });
        
        // Apply sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        
        // Handle special sorting cases
        if ($sortField === 'priority') {
            $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')");
        } else {
            $query->orderBy($sortField, $sortDirection);
        }
        
        // Get paginated results
        $tickets = $query->paginate($request->input('per_page', 20));
        
        // Add summary statistics
        $stats = [
            'total' => $tickets->total(),
            'open_count' => (clone $query->getQuery())->whereIn('status', [
                Ticket::STATUS_OPEN, 
                Ticket::STATUS_IN_PROGRESS, 
                Ticket::STATUS_WAITING_CUSTOMER
            ])->count(),
            'overdue_count' => (clone $query->getQuery())->where('due_date', '<', now())->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'urgent_count' => (clone $query->getQuery())->where('priority', Ticket::PRIORITY_URGENT)->count(),
        ];
        
        return response()->json([
            'data' => TicketResource::collection($tickets),
            'meta' => array_merge($tickets->toArray(), ['stats' => $stats])
        ]);
    }

    /**
     * Display tickets page (Inertia)
     */
    public function indexView(Request $request)
    {
        $user = $request->user();
        
        // Determine if user can view all accounts (service provider staff)
        $canViewAllAccounts = $user->hasAnyPermission([
            'tickets.view.all', 
            'admin.read', 
            'system.manage',
            'accounts.manage'
        ]);
        
        // Get tickets via API logic
        $apiRequest = clone $request;
        $apiRequest->merge(['per_page' => 50]); // Get more for initial load
        
        $response = $this->index($apiRequest);
        $responseData = json_decode($response->getContent(), true);
        
        // Get available accounts for service provider users
        $availableAccounts = [];
        if ($canViewAllAccounts) {
            $availableAccounts = Account::select('id', 'name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }
        
        // User permissions for the frontend
        $permissions = [
            'canCreateTickets' => $user->hasAnyPermission(['tickets.create', 'admin.write']),
            'canEditAllTickets' => $user->hasAnyPermission(['tickets.edit.all', 'admin.write']),
            'canAssignTickets' => $user->hasAnyPermission(['tickets.assign', 'admin.write']),
            'canViewAllAccounts' => $canViewAllAccounts,
        ];
        
        return Inertia::render('Tickets/Index', [
            'initialTickets' => $responseData['data'],
            'availableAccounts' => $availableAccounts,
            'canViewAllAccounts' => $canViewAllAccounts,
            'permissions' => $permissions,
            'stats' => $responseData['meta']['stats'] ?? []
        ]);
    }

    /**
     * Store a newly created ticket
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'account_id' => 'nullable|exists:accounts,id',
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'category' => ['required', Rule::in(['support', 'maintenance', 'development', 'consulting', 'other'])],
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date|after:now',
            'estimated_hours' => 'nullable|integer|min:1|max:1000',
            'estimated_amount' => 'nullable|numeric|min:0|max:999999.99',
            'billing_rate_id' => 'nullable|exists:billing_rates,id',
            'metadata' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);
        
        // Determine account_id if not provided
        if (empty($validated['account_id'])) {
            // If user is super admin or has system-wide permissions, use their first available account
            if ($user->isSuperAdmin() || $user->hasAnyPermission(['admin.write', 'accounts.manage', 'tickets.view.all'])) {
                $userAccount = $user->accounts()->first();
                if (!$userAccount) {
                    return response()->json(['error' => 'No account available for ticket creation.'], 422);
                }
                $validated['account_id'] = $userAccount->id;
            } else {
                return response()->json(['error' => 'Account ID is required.'], 422);
            }
        }
        
        // Verify user has access to the specified account (unless they have system-wide permissions)
        if (!$user->isSuperAdmin() && !$user->hasAnyPermission(['admin.write', 'accounts.manage', 'tickets.view.all'])) {
            if (!$user->accounts()->where('accounts.id', $validated['account_id'])->exists()) {
                return response()->json(['error' => 'You do not have access to this account.'], 403);
            }
        }
        
        // Verify assigned user has access to the account if specified
        if (!empty($validated['assigned_to'])) {
            $assignedUser = User::find($validated['assigned_to']);
            if (!$assignedUser->accounts()->where('accounts.id', $validated['account_id'])->exists()) {
                return response()->json(['error' => 'Assigned user does not have access to this account.'], 422);
            }
        }
        
        // Create the ticket
        $ticket = Ticket::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'account_id' => $validated['account_id'],
            'priority' => $validated['priority'],
            'category' => $validated['category'],
            'created_by' => $user->id,
            'assigned_to_id' => $validated['assigned_to'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'estimated_amount' => $validated['estimated_amount'] ?? null,
            'billing_rate_id' => $validated['billing_rate_id'] ?? null,
            'status' => $validated['assigned_to'] ? Ticket::STATUS_IN_PROGRESS : Ticket::STATUS_OPEN,
            'metadata' => $validated['metadata'] ?? null,
            'settings' => $validated['settings'] ?? null,
        ]);
        
        $ticket->load(['account:id,name', 'createdBy:id,name', 'assignedTo:id,name']);
        
        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => 'Ticket created successfully.'
        ], 201);
    }

    /**
     * Display the specified ticket
     */
    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();
        
        // Check if user can view this ticket
        if (!$ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }
        
        $ticket->load([
            'account:id,name', 
            'createdBy:id,name,email', 
            'assignedTo:id,name,email',
            'assignedUsers:id,name,email',
            'billingRate:id,rate,currency',
            'timeEntries:id,duration,billable,created_at',
            'timers:id,status,started_at,duration',
            'statusModel',
            'categoryModel',
            'addons'
        ]);
        
        return response()->json([
            'data' => new TicketResource($ticket)
        ]);
    }

    /**
     * Show ticket view (Inertia)
     */
    public function showView(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        
        // Check permissions
        $this->authorize('view', $ticket);
        
        // Load ticket with all related data
        $ticket->load([
            'account',
            'assignedTo',
            'createdBy', 
            'timers.user',
            'timeEntries.user',
            'statusModel',
            'categoryModel',
            'addons'
        ]);
        
        return Inertia::render('Tickets/Show', [
            'ticket' => new TicketResource($ticket),
        ]);
    }

    /**
     * Show ticket create form (Inertia)
     */
    public function create(Request $request)
    {
        $user = $request->user();
        
        // Check permissions
        $this->authorize('create', Ticket::class);
        
        $availableAccounts = [];
        $canAssignTickets = $user->hasAnyPermission(['tickets.assign', 'admin.write']);
        
        // Get available accounts
        if ($user->hasAnyPermission(['tickets.view.all', 'admin.read', 'accounts.manage'])) {
            $availableAccounts = Account::select('id', 'name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }
        
        return Inertia::render('Tickets/Create', [
            'availableAccounts' => $availableAccounts,
            'canAssignTickets' => $canAssignTickets,
        ]);
    }

    /**
     * Show ticket edit form (Inertia)
     */
    public function edit(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        
        // Check permissions
        $this->authorize('update', $ticket);
        
        $ticket->load(['account', 'assignedTo', 'createdBy']);
        
        $availableAccounts = [];
        $assignableUsers = [];
        $canAssignTickets = $user->hasAnyPermission(['tickets.assign', 'admin.write']);
        
        // Get available accounts
        if ($user->hasAnyPermission(['tickets.view.all', 'admin.read', 'accounts.manage'])) {
            $availableAccounts = Account::select('id', 'name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }
        
        // Get assignable users
        if ($canAssignTickets) {
            $assignableUsers = User::select('id', 'name')
                ->whereHas('roleTemplates', function ($query) {
                    $query->whereHas('permissions', function ($q) {
                        $q->where('name', 'LIKE', 'tickets.%');
                    });
                })
                ->orderBy('name')
                ->get();
        }
        
        return Inertia::render('Tickets/Edit', [
            'ticket' => new TicketResource($ticket),
            'availableAccounts' => $availableAccounts,
            'assignableUsers' => $assignableUsers,
            'canAssignTickets' => $canAssignTickets,
        ]);
    }

    /**
     * Update the specified ticket
     */
    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();
        
        // Check if user can edit this ticket
        if (!$ticket->canBeEditedBy($user)) {
            return response()->json(['error' => 'Cannot edit this ticket.'], 403);
        }
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:10000',
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'category' => ['sometimes', Rule::in(['support', 'maintenance', 'development', 'consulting', 'other'])],
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'due_date' => 'sometimes|nullable|date|after:now',
            'estimated_hours' => 'sometimes|nullable|integer|min:1|max:1000',
            'estimated_amount' => 'sometimes|nullable|numeric|min:0|max:999999.99',
            'actual_amount' => 'sometimes|nullable|numeric|min:0|max:999999.99',
            'billing_rate_id' => 'sometimes|nullable|exists:billing_rates,id',
            'metadata' => 'sometimes|nullable|array',
            'settings' => 'sometimes|nullable|array',
        ]);
        
        // Verify assigned user has access to the account if specified
        if (isset($validated['assigned_to']) && $validated['assigned_to']) {
            $assignedUser = User::find($validated['assigned_to']);
            if (!$assignedUser->accounts()->where('accounts.id', $ticket->account_id)->exists()) {
                return response()->json(['error' => 'Assigned user does not have access to this account.'], 422);
            }
        }
        
        $ticket->update($validated);
        $ticket->load(['account:id,name', 'createdBy:id,name', 'assignedTo:id,name']);
        
        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => 'Ticket updated successfully.'
        ]);
    }

    /**
     * Remove the specified ticket
     */
    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();
        
        // Only admins can delete tickets
        if (!$user->isSuperAdmin() && !$user->hasAnyPermission(['admin.write'])) {
            return response()->json(['error' => 'Only administrators can delete tickets.'], 403);
        }
        
        // Check account access
        if (!$user->accounts()->where('accounts.id', $ticket->account_id)->exists()) {
            return response()->json(['error' => 'You do not have access to this account.'], 403);
        }
        
        $ticket->delete();
        
        return response()->json([
            'message' => 'Ticket deleted successfully.'
        ]);
    }
    
    /**
     * Workflow Actions
     */
    
    /**
     * Transition ticket to a new status
     */
    public function transitionStatus(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();
        
        if (!$ticket->canBeEditedBy($user)) {
            return response()->json(['error' => 'Cannot modify this ticket.'], 403);
        }
        
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Ticket::STATUS_OPEN,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_WAITING_CUSTOMER,
                Ticket::STATUS_ON_HOLD,
                Ticket::STATUS_RESOLVED,
                Ticket::STATUS_CLOSED,
                Ticket::STATUS_CANCELLED
            ])],
            'notes' => 'nullable|string|max:2000'
        ]);
        
        if (!$ticket->canTransitionTo($validated['status'])) {
            return response()->json([
                'error' => "Cannot transition from {$ticket->status} to {$validated['status']}."
            ], 422);
        }
        
        $success = $ticket->transitionTo($validated['status'], $user, $validated['notes'] ?? null);
        
        if (!$success) {
            return response()->json(['error' => 'Failed to update ticket status.'], 500);
        }
        
        $ticket->load(['account:id,name', 'createdBy:id,name', 'assignedTo:id,name']);
        
        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => "Ticket status updated to {$validated['status']}."
        ]);
    }
    
    /**
     * Assign ticket to a user
     */
    public function assign(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions
        if (!$user->hasAnyPermission(['teams.manage', 'admin.write', 'tickets.assign'])) {
            return response()->json(['error' => 'Insufficient permissions to assign tickets.'], 403);
        }
        
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);
        
        $assignedUser = User::find($validated['assigned_to']);
        
        // Verify assigned user has access to the account
        if (!$assignedUser->accounts()->where('accounts.id', $ticket->account_id)->exists()) {
            return response()->json(['error' => 'User does not have access to this account.'], 422);
        }
        
        $success = $ticket->assignTo($assignedUser);
        
        if (!$success) {
            return response()->json(['error' => 'Failed to assign ticket.'], 500);
        }
        
        $ticket->load(['account:id,name', 'createdBy:id,name', 'assignedTo:id,name']);
        
        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => "Ticket assigned to {$assignedUser->name}."
        ]);
    }
    
    /**
     * Get ticket statistics for dashboard widgets
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Build base query with user's accessible tickets
        $query = Ticket::query();
        
        if ($user->isSuperAdmin() || $user->hasAnyPermission(['admin.read'])) {
            $accountIds = $user->accounts()->pluck('accounts.id');
            $query->whereIn('account_id', $accountIds);
        } elseif ($user->hasAnyPermission(['teams.manage'])) {
            $managedAccountIds = $user->accounts()
                ->whereHas('users', function ($userQuery) use ($user) {
                    $userQuery->where('users.id', $user->id)
                             ->whereHas('roleTemplates', function ($roleQuery) {
                                 $roleQuery->whereJsonContains('permissions', 'teams.manage');
                             });
                })
                ->pluck('accounts.id');
            $query->whereIn('account_id', $managedAccountIds);
        } else {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('assigned_to_id', $user->id);
            });
        }
        
        $stats = [
            'total' => (clone $query)->count(),
            'open' => (clone $query)->where('status', Ticket::STATUS_OPEN)->count(),
            'in_progress' => (clone $query)->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
            'waiting_customer' => (clone $query)->where('status', Ticket::STATUS_WAITING_CUSTOMER)->count(),
            'on_hold' => (clone $query)->where('status', Ticket::STATUS_ON_HOLD)->count(),
            'resolved' => (clone $query)->where('status', Ticket::STATUS_RESOLVED)->count(),
            'closed' => (clone $query)->where('status', Ticket::STATUS_CLOSED)->count(),
            'overdue' => (clone $query)->where('due_date', '<', now())->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'high_priority' => (clone $query)->whereIn('priority', [Ticket::PRIORITY_HIGH, Ticket::PRIORITY_URGENT])->count(),
            'assigned_to_me' => (clone $query)->where('assigned_to_id', $user->id)->count(),
        ];
        
        return response()->json(['data' => $stats]);
    }
    
    /**
     * Get tickets assigned to the current user
     */
    public function myTickets(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = Ticket::with(['account:id,name', 'createdBy:id,name'])
            ->where('assigned_to_id', $user->id);
        
        // Apply status filter if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to open tickets
            $query->whereNotIn('status', [Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED]);
        }
        
        $tickets = $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
                        ->orderBy('due_date', 'asc')
                        ->paginate($request->input('per_page', 10));
        
        return response()->json([
            'data' => TicketResource::collection($tickets),
            'meta' => $tickets->toArray()
        ]);
    }
    
    /**
     * Team Assignment Actions
     */
    
    /**
     * Add a user to the ticket team
     */
    public function addTeamMember(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions
        if (!$user->hasAnyPermission(['teams.manage', 'admin.write'])) {
            return response()->json(['error' => 'Insufficient permissions to manage ticket teams.'], 403);
        }
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string|max:50',
            'assignment_notes' => 'nullable|string|max:500'
        ]);
        
        $teamMember = User::find($validated['user_id']);
        
        // Verify team member has access to the account
        if (!$teamMember->accounts()->where('accounts.id', $ticket->account_id)->exists()) {
            return response()->json(['error' => 'User does not have access to this account.'], 422);
        }
        
        // Check if already assigned
        if ($ticket->assignedUsers()->where('users.id', $teamMember->id)->exists()) {
            return response()->json(['error' => 'User is already assigned to this ticket.'], 422);
        }
        
        $ticket->assignedUsers()->attach($teamMember->id, [
            'role' => $validated['role'] ?? 'assignee',
            'assigned_at' => now()
        ]);
        
        $ticket->load(['assignedUsers:id,name,email']);
        
        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => "Added {$teamMember->name} to ticket team."
        ]);
    }
    
    /**
     * Remove a user from the ticket team
     */
    public function removeTeamMember(Request $request, Ticket $ticket, User $teamMember): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions
        if (!$user->hasAnyPermission(['teams.manage', 'admin.write'])) {
            return response()->json(['error' => 'Insufficient permissions to manage ticket teams.'], 403);
        }
        
        // Check if user is assigned to this ticket
        if (!$ticket->assignedUsers()->where('users.id', $teamMember->id)->exists()) {
            return response()->json(['error' => 'User is not assigned to this ticket.'], 422);
        }
        
        $ticket->assignedUsers()->detach($teamMember->id);
        $ticket->load(['assignedUsers:id,name,email']);
        
        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => "Removed {$teamMember->name} from ticket team."
        ]);
    }
    
    /**
     * Get filter counts for tickets dashboard
     */
    public function filterCounts(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Build base query with user's accessible accounts
        $baseQuery = Ticket::query();
        
        // Apply user scope - employees see assigned tickets, managers/admins see team tickets
        if ($user->isSuperAdmin() || $user->hasAnyPermission(['admin.read', 'admin.write'])) {
            // Admins see all tickets in their accounts
            $accountIds = $user->accounts()->pluck('accounts.id');
            $baseQuery->whereIn('account_id', $accountIds);
        } elseif ($user->hasAnyPermission(['teams.manage', 'tickets.view.all'])) {
            // Managers see tickets in accounts they manage
            $managedAccountIds = $user->accounts()
                ->whereHas('users', function ($userQuery) use ($user) {
                    $userQuery->where('users.id', $user->id)
                             ->whereHas('roleTemplates', function ($roleQuery) {
                                 $roleQuery->whereJsonContains('permissions', 'teams.manage');
                             });
                })
                ->pluck('accounts.id');
            $baseQuery->whereIn('account_id', $managedAccountIds);
        } else {
            // Regular employees see only their assigned tickets
            $baseQuery->where(function ($query) use ($user) {
                $query->where('assigned_to_id', $user->id)
                      ->orWhereHas('assignedUsers', function ($assignedQuery) use ($user) {
                          $assignedQuery->where('users.id', $user->id);
                      });
            });
        }
        
        // Apply filters if provided
        if ($request->has('selectedAccount') && $request->input('selectedAccount.id')) {
            $baseQuery->where('account_id', $request->input('selectedAccount.id'));
        }
        
        $counts = [
            'all' => (clone $baseQuery)->count(),
            'open' => (clone $baseQuery)->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'closed' => (clone $baseQuery)->whereIn('status', ['closed'])->count(),
            'assigned_to_me' => (clone $baseQuery)->where('assigned_to_id', $user->id)->count(),
            'high_priority' => (clone $baseQuery)->where('priority', 'high')->count(),
            'overdue' => (clone $baseQuery)->where('due_date', '<', now())->whereNotIn('status', ['closed', 'cancelled'])->count(),
        ];
        
        return response()->json([
            'data' => $counts
        ]);
    }
}