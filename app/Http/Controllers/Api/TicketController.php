<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Account;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

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
            'billingRate:id,rate',
            'timers' => function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->whereIn('status', ['running', 'paused'])
                    ->with(['user:id,name', 'billingRate:id,rate']);
            },
        ]);

        // Apply user scope based on permissions
        if ($user->isSuperAdmin() || $user->hasAnyPermission(['tickets.admin', 'tickets.view.all'])) {
            // Global access - Super Admin and Admin roles see all tickets
            // No filtering applied
        } elseif ($user->hasAnyPermission(['admin.read', 'admin.write', 'tickets.view.account'])) {
            // Account-scoped access - see tickets within their account
            if ($user->account) {
                // Check if user has hierarchical access (Account Manager)
                if ($user->hasPermission('accounts.hierarchy.access')) {
                    // Include tickets from own account + all child accounts
                    $accessibleAccountIds = $user->account->getAccessibleAccountIds();
                    $query->whereIn('account_id', $accessibleAccountIds);
                } else {
                    // Regular account user - only their own account
                    $query->where('account_id', $user->account->id);
                }
            }
        } else {
            // Personal scope - Agents and Account Users see assigned/owned tickets only
            $query->where(function ($q) use ($user) {
                $q->where('created_by_id', $user->id)
                    ->orWhere('agent_id', $user->id)
                    ->orWhere('customer_id', $user->id)  // Include tickets where user is the customer
                    ->orWhereHas('assignedUsers', function ($assignedQuery) use ($user) {
                        $assignedQuery->where('users.id', $user->id);
                    });
            });
        }

        // Apply filters
        $query->when($request->status, function ($q, $status) use ($request) {
            if (is_array($status)) {
                if ($request->include_ticket_id) {
                    // Include specific ticket even if it doesn't match status filter
                    $q->where(function ($subQuery) use ($status, $request) {
                        $subQuery->whereIn('status', $status)
                            ->orWhere('id', $request->include_ticket_id);
                    });
                } else {
                    $q->whereIn('status', $status);
                }
            } else {
                if ($request->include_ticket_id) {
                    // Include specific ticket even if it doesn't match status filter
                    $q->where(function ($subQuery) use ($status, $request) {
                        $subQuery->where('status', $status)
                            ->orWhere('id', $request->include_ticket_id);
                    });
                } else {
                    $q->where('status', $status);
                }
            }
        })->when($request->priority, function ($q, $priority) {
            if (is_array($priority)) {
                $q->whereIn('priority', $priority);
            } else {
                $q->where('priority', $priority);
            }
        })->when($request->category, function ($q, $category) {
            $q->where('category', $category);
        })->when($request->agent_id, function ($q, $agentId) {
            if ($agentId === 'mine') {
                $q->where('agent_id', $user->id);
            } elseif ($agentId === 'unassigned') {
                $q->whereNull('agent_id');
            } else {
                $q->where('agent_id', $agentId);
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
                Ticket::STATUS_WAITING_CUSTOMER,
            ])->count(),
            'overdue_count' => (clone $query->getQuery())->where('due_date', '<', now())->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'urgent_count' => (clone $query->getQuery())->where('priority', Ticket::PRIORITY_URGENT)->count(),
        ];

        return response()->json([
            'data' => TicketResource::collection($tickets),
            'meta' => array_merge($tickets->toArray(), ['stats' => $stats]),
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
            'tickets.admin',
            'admin.read',
            'admin.write',
            'system.manage',
            'accounts.manage',
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
            'stats' => $responseData['meta']['stats'] ?? [],
        ]);
    }

    /**
     * Store a newly created ticket
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get available categories for validation
        $availableCategories = TicketCategory::getOptions();
        $categoryKeys = collect($availableCategories)->pluck('key')->toArray();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'account_id' => 'nullable|exists:accounts,id',
            'customer_id' => 'nullable|exists:users,id',
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'category' => ['nullable', Rule::in($categoryKeys)],
            'agent_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date|after:now',
            'estimated_hours' => 'nullable|integer|min:1|max:1000',
            'estimated_amount' => 'nullable|numeric|min:0|max:999999.99',
            'billing_rate_id' => 'nullable|exists:billing_rates,id',
            'metadata' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        // ABAC Security: Auto-assign and enforce account for account users
        if ($user->user_type === 'account_user') {
            // Account users can ONLY create tickets for their own account
            if (! $user->account_id) {
                return response()->json(['error' => 'Account user must be assigned to an account.'], 422);
            }
            // Override any provided account_id for security
            $validated['account_id'] = $user->account_id;
        } else {
            // Determine account_id if not provided for other user types
            if (empty($validated['account_id'])) {
                // If user is super admin or has system-wide permissions, use their account
                if ($user->isSuperAdmin() || $user->hasAnyPermission(['admin.write', 'accounts.manage', 'tickets.view.all'])) {
                    if (! $user->account_id) {
                        return response()->json(['error' => 'No account available for ticket creation.'], 422);
                    }
                    $validated['account_id'] = $user->account_id;
                } else {
                    return response()->json(['error' => 'Account ID is required.'], 422);
                }
            }

            // Verify user has access to the specified account (unless they have system-wide permissions)
            if (! $user->isSuperAdmin() && ! $user->hasAnyPermission(['admin.write', 'accounts.manage', 'tickets.view.all'])) {
                if ($user->account_id !== $validated['account_id']) {
                    return response()->json(['error' => 'You do not have access to this account.'], 403);
                }
            }
        }

        // Set default category if not provided
        if (empty($validated['category'])) {
            $defaultCategory = TicketCategory::getDefault();
            if ($defaultCategory) {
                $validated['category'] = $defaultCategory->key;
            } elseif (count($categoryKeys) > 0) {
                // Fallback to first available category if no default is set
                $validated['category'] = $categoryKeys[0];
            } else {
                return response()->json(['error' => 'No categories available for ticket creation.'], 422);
            }
        }

        // Verify assigned user has access to the account if specified
        if (! empty($validated['agent_id'])) {
            $assignedUser = User::find($validated['agent_id']);
            if ($assignedUser) {
                // Allow assignment if:
                // 1. User belongs to the same account
                // 2. User is a Super Admin
                // 3. User has tickets.act_as_agent permission (cross-account agent)
                // 4. User has admin.write permission (system admin)
                $canAssign = $assignedUser->account_id === $validated['account_id'] ||
                           $assignedUser->isSuperAdmin() ||
                           $assignedUser->hasPermission('tickets.act_as_agent') ||
                           $assignedUser->hasPermission('admin.write');

                if (! $canAssign) {
                    return response()->json(['error' => 'Assigned user does not have access to this account.'], 422);
                }
            }
        }

        // Create the ticket
        $ticket = Ticket::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'account_id' => $validated['account_id'],
            'customer_id' => $validated['customer_id'] ?? null,
            'priority' => $validated['priority'],
            'category' => $validated['category'],
            'created_by_id' => $user->id,
            'agent_id' => $validated['agent_id'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'estimated_amount' => $validated['estimated_amount'] ?? null,
            'billing_rate_id' => $validated['billing_rate_id'] ?? null,
            'status' => ($validated['agent_id'] ?? null) ? Ticket::STATUS_IN_PROGRESS : Ticket::STATUS_OPEN,
            'metadata' => $validated['metadata'] ?? null,
            'settings' => $validated['settings'] ?? null,
        ]);

        $ticket->load(['account:id,name', 'createdBy:id,name', 'assignedTo:id,name']);

        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => 'Ticket created successfully.',
        ], 201);
    }

    /**
     * Display the specified ticket
     */
    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        // Check if user can view this ticket
        if (! $ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $ticket->load([
            'account:id,name',
            'createdBy:id,name,email',
            'assignedTo:id,name,email',
            'assignedUsers:id,name,email',
            'billingRate:id,rate',
            'timeEntries:id,duration,billable,created_at',
            'timers:id,status,started_at,duration',
            'statusModel',
            'categoryModel',
            'addons',
        ]);

        return response()->json([
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * Show ticket view (Inertia)
     */
    public function showView(Request $request, Ticket $ticket, $tab = null)
    {
        $user = $request->user();

        // Check permissions
        $this->authorize('view', $ticket);

        // Load ticket with permission-based related data
        $relations = [
            'account',
            'assignedTo',
            'createdBy',
            'statusModel',
            'categoryModel',
        ];

        // Only load timer data if user has timer permissions
        if ($user->hasAnyPermission(['timers.read', 'timers.write', 'time.track'])) {
            $relations[] = 'timers.user';
        }

        // Only load time entries if user has time viewing permissions
        if ($user->hasAnyPermission(['time.view.own', 'time.view.account', 'time.view.all', 'time.track'])) {
            $relations[] = 'timeEntries.user';
        }

        // Only load add-ons if user can view them
        if ($ticket->canBeViewedBy($user)) {
            $relations[] = 'addons';
        }

        $ticket->load($relations);

        // Get user permissions for the frontend
        $permissions = [
            // Timer permissions (for Time Tracking tab)
            'canViewTimers' => $user->hasAnyPermission(['timers.read', 'timers.write', 'time.track']),
            'canCreateTimers' => $user->hasAnyPermission(['timers.create', 'timers.write']),

            // Time entry permissions (for Time Tracking tab)
            'canViewTimeEntries' => $user->hasAnyPermission(['time.view.own', 'time.view.account', 'time.view.all', 'time.track']),
            'canCreateTimeEntries' => $user->hasAnyPermission(['time.create', 'time.track']),

            // Comment permissions (for Messages tab)
            'canViewComments' => $ticket->canBeViewedBy($user), // If can view ticket, can view comments
            'canAddComments' => $user->hasAnyPermission(['tickets.comment', 'tickets.edit.account', 'tickets.view.account']),
            'canViewInternalComments' => $user->hasAnyPermission(['admin.read', 'tickets.manage', 'tickets.view.internal']),

            // Add-on permissions (for Add-ons tab)
            'canViewAddons' => $ticket->canBeViewedBy($user), // If can view ticket, can view add-ons
            'canManageAddons' => $user->hasAnyPermission(['admin.write', 'tickets.manage']) || ($user->user_type === 'agent' && $user->hasPermission('tickets.edit.account')),

            // Activity permissions (for Activity tab)
            'canViewActivity' => $ticket->canBeViewedBy($user), // If can view ticket, can view activity

            // Billing permissions (for Billing tab)
            'canViewBilling' => $user->hasAnyPermission(['billing.view.account', 'billing.view.all', 'admin.read']),
            'canManageBilling' => $user->hasAnyPermission(['billing.manage', 'admin.write']),

            // General ticket permissions
            'canEditTicket' => $ticket->canBeEditedBy($user),
            'canAssignTicket' => $user->hasAnyPermission(['tickets.assign', 'tickets.assign.account', 'admin.write']),
        ];

        // Validate tab parameter against available tabs
        $validTabs = ['messages', 'time', 'addons', 'activity', 'billing'];
        $activeTab = null;

        if ($tab && in_array($tab, $validTabs)) {
            $activeTab = $tab;
        }

        return Inertia::render('Tickets/Show', [
            'ticketId' => $ticket->id,
            'ticket' => new TicketResource($ticket),
            'permissions' => $permissions,
            'activeTab' => $activeTab,
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
        if ($user->hasAnyPermission(['tickets.view.all', 'tickets.admin', 'admin.read', 'admin.write', 'accounts.manage'])) {
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
     * Update the specified ticket
     */
    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        // Check if user can edit this ticket
        if (! $ticket->canBeEditedBy($user)) {
            return response()->json(['error' => 'Cannot edit this ticket.'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:10000',
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'category' => ['sometimes', Rule::in(['support', 'maintenance', 'development', 'consulting', 'other'])],
            'agent_id' => 'sometimes|nullable|exists:users,id',
            'due_date' => 'sometimes|nullable|date|after:now',
            'estimated_hours' => 'sometimes|nullable|integer|min:1|max:1000',
            'estimated_amount' => 'sometimes|nullable|numeric|min:0|max:999999.99',
            'actual_amount' => 'sometimes|nullable|numeric|min:0|max:999999.99',
            'billing_rate_id' => 'sometimes|nullable|exists:billing_rates,id',
            'metadata' => 'sometimes|nullable|array',
            'settings' => 'sometimes|nullable|array',
        ]);

        // Verify assigned user has access to the account if specified
        if (isset($validated['agent_id']) && $validated['agent_id']) {
            $assignedUser = User::find($validated['agent_id']);
            if ($assignedUser) {
                // Allow assignment if:
                // 1. User belongs to the same account
                // 2. User is a Super Admin
                // 3. User has tickets.act_as_agent permission (cross-account agent)
                // 4. User has admin.write permission (system admin)
                $canAssign = $assignedUser->account_id === $ticket->account_id ||
                           $assignedUser->isSuperAdmin() ||
                           $assignedUser->hasPermission('tickets.act_as_agent') ||
                           $assignedUser->hasPermission('admin.write');

                if (! $canAssign) {
                    return response()->json(['error' => 'Assigned user does not have access to this account.'], 422);
                }
            }
        }

        $ticket->update($validated);
        $ticket->load(['account:id,name', 'createdBy:id,name', 'assignedTo:id,name']);

        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => 'Ticket updated successfully.',
        ]);
    }

    /**
     * Remove the specified ticket
     */
    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        // Only admins can delete tickets
        if (! $user->isSuperAdmin() && ! $user->hasAnyPermission(['admin.write'])) {
            return response()->json(['error' => 'Only administrators can delete tickets.'], 403);
        }

        // Check account access
        if (! $user->isSuperAdmin() && $user->account_id !== $ticket->account_id) {
            return response()->json(['error' => 'You do not have access to this account.'], 403);
        }

        $ticket->delete();

        return response()->json([
            'message' => 'Ticket deleted successfully.',
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

        if (! $ticket->canBeEditedBy($user)) {
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
                Ticket::STATUS_CANCELLED,
            ])],
            'notes' => 'nullable|string|max:2000',
        ]);

        if (! $ticket->canTransitionTo($validated['status'])) {
            return response()->json([
                'error' => "Cannot transition from {$ticket->status} to {$validated['status']}.",
            ], 422);
        }

        $success = $ticket->transitionTo($validated['status'], $user, $validated['notes'] ?? null);

        if (! $success) {
            return response()->json(['error' => 'Failed to update ticket status.'], 500);
        }

        $ticket->load(['account:id,name', 'createdBy:id,name', 'assignedTo:id,name']);

        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => "Ticket status updated to {$validated['status']}.",
        ]);
    }

    /**
     * Update ticket priority
     */
    public function updatePriority(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        if (! $ticket->canBeEditedBy($user)) {
            return response()->json(['error' => 'Cannot modify this ticket.'], 403);
        }

        $validated = $request->validate([
            'priority' => ['required', Rule::in([
                Ticket::PRIORITY_LOW,
                Ticket::PRIORITY_NORMAL,
                Ticket::PRIORITY_MEDIUM,
                Ticket::PRIORITY_HIGH,
                Ticket::PRIORITY_URGENT,
            ])],
            'notes' => 'nullable|string|max:500',
        ]);

        $oldPriority = $ticket->priority;
        $ticket->update(['priority' => $validated['priority']]);

        // Log the priority change in ticket activity
        $ticket->activities()->create([
            'user_id' => $user->id,
            'action' => 'priority_changed',
            'description' => "Priority changed from {$oldPriority} to {$validated['priority']}",
            'metadata' => [
                'from_priority' => $oldPriority,
                'to_priority' => $validated['priority'],
                'notes' => $validated['notes'] ?? null,
            ],
        ]);

        $ticket->load(['account:id,name', 'createdBy:id,name', 'assignedTo:id,name']);

        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => "Ticket priority updated to {$validated['priority']}.",
        ]);
    }

    /**
     * Assign ticket to a user
     */
    public function assign(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        // Check permissions
        if (! $user->hasAnyPermission(['tickets.assign', 'tickets.assign.account', 'admin.write'])) {
            return response()->json(['error' => 'Insufficient permissions to assign tickets.'], 403);
        }

        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        $assignedUser = User::find($validated['agent_id']);

        // Verify assigned user has access to the account
        // Allow assignment if:
        // 1. User belongs to the same account
        // 2. User is a Super Admin
        // 3. User has tickets.act_as_agent permission (cross-account agent)
        // 4. User has admin.write permission (system admin)
        $canAssign = $assignedUser->account_id === $ticket->account_id ||
                   $assignedUser->isSuperAdmin() ||
                   $assignedUser->hasPermission('tickets.act_as_agent') ||
                   $assignedUser->hasPermission('admin.write');

        if (! $canAssign) {
            return response()->json(['error' => 'User does not have access to this account.'], 422);
        }

        $success = $ticket->assignTo($assignedUser);

        if (! $success) {
            return response()->json(['error' => 'Failed to assign ticket.'], 500);
        }

        $ticket->load(['account:id,name', 'createdBy:id,name', 'assignedTo:id,name']);

        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => "Ticket assigned to {$assignedUser->name}.",
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

        if ($user->isSuperAdmin() || $user->hasAnyPermission(['tickets.admin', 'tickets.view.all'])) {
            // Global access - see all tickets
            // No filtering applied
        } elseif ($user->hasAnyPermission(['admin.read', 'admin.write', 'tickets.view.account'])) {
            // Account-scoped access
            if ($user->account) {
                // Check if user has hierarchical access (Account Manager)
                if ($user->hasPermission('accounts.hierarchy.access')) {
                    // Include tickets from own account + all child accounts
                    $accessibleAccountIds = $user->account->getAccessibleAccountIds();
                    $query->whereIn('account_id', $accessibleAccountIds);
                } else {
                    // Regular account user - only their own account
                    $query->where('account_id', $user->account->id);
                }
            }
        } else {
            // Personal scope
            $query->where(function ($q) use ($user) {
                $q->where('created_by_id', $user->id)
                    ->orWhere('agent_id', $user->id);
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
            'assigned_to_me' => (clone $query)->where('agent_id', $user->id)->count(),
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
            ->where('agent_id', $user->id);

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
            'meta' => $tickets->toArray(),
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
        if (! $user->hasAnyPermission(['tickets.assign', 'tickets.assign.account', 'admin.write'])) {
            return response()->json(['error' => 'Insufficient permissions to manage ticket teams.'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string|max:50',
            'assignment_notes' => 'nullable|string|max:500',
        ]);

        $teamMember = User::find($validated['user_id']);

        // Verify team member has access to the account
        // Allow assignment if:
        // 1. User belongs to the same account
        // 2. User is a Super Admin
        // 3. User has tickets.act_as_agent permission (cross-account agent)
        // 4. User has admin.write permission (system admin)
        $canAssign = $teamMember->account_id === $ticket->account_id ||
                   $teamMember->isSuperAdmin() ||
                   $teamMember->hasPermission('tickets.act_as_agent') ||
                   $teamMember->hasPermission('admin.write');

        if (! $canAssign) {
            return response()->json(['error' => 'User does not have access to this account.'], 422);
        }

        // Check if already assigned
        if ($ticket->assignedUsers()->where('users.id', $teamMember->id)->exists()) {
            return response()->json(['error' => 'User is already assigned to this ticket.'], 422);
        }

        $ticket->assignedUsers()->attach($teamMember->id, [
            'role' => $validated['role'] ?? 'assignee',
            'assigned_at' => now(),
        ]);

        $ticket->load(['assignedUsers:id,name,email']);

        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => "Added {$teamMember->name} to ticket team.",
        ]);
    }

    /**
     * Remove a user from the ticket team
     */
    public function removeTeamMember(Request $request, Ticket $ticket, User $teamMember): JsonResponse
    {
        $user = $request->user();

        // Check permissions
        if (! $user->hasAnyPermission(['tickets.assign', 'tickets.assign.account', 'admin.write'])) {
            return response()->json(['error' => 'Insufficient permissions to manage ticket teams.'], 403);
        }

        // Check if user is assigned to this ticket
        if (! $ticket->assignedUsers()->where('users.id', $teamMember->id)->exists()) {
            return response()->json(['error' => 'User is not assigned to this ticket.'], 422);
        }

        $ticket->assignedUsers()->detach($teamMember->id);
        $ticket->load(['assignedUsers:id,name,email']);

        return response()->json([
            'data' => new TicketResource($ticket),
            'message' => "Removed {$teamMember->name} from ticket team.",
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

        // Apply user scope based on permissions
        if ($user->isSuperAdmin() || $user->hasAnyPermission(['tickets.admin', 'tickets.view.all'])) {
            // Global access - see all tickets
            // No filtering applied
        } elseif ($user->hasAnyPermission(['admin.read', 'admin.write', 'tickets.view.account'])) {
            // Account-scoped access
            if ($user->account) {
                // Check if user has hierarchical access (Account Manager)
                if ($user->hasPermission('accounts.hierarchy.access')) {
                    // Include tickets from own account + all child accounts
                    $accessibleAccountIds = $user->account->getAccessibleAccountIds();
                    $baseQuery->whereIn('account_id', $accessibleAccountIds);
                } else {
                    // Regular account user - only their own account
                    $baseQuery->where('account_id', $user->account->id);
                }
            }
        } else {
            // Personal scope - see assigned/owned tickets only
            $baseQuery->where(function ($query) use ($user) {
                $query->where('created_by_id', $user->id)
                    ->orWhere('agent_id', $user->id)
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
            'assigned_to_me' => (clone $baseQuery)->where('agent_id', $user->id)->count(),
            'high_priority' => (clone $baseQuery)->where('priority', 'high')->count(),
            'overdue' => (clone $baseQuery)->where('due_date', '<', now())->whereNotIn('status', ['closed', 'cancelled'])->count(),
        ];

        return response()->json([
            'data' => $counts,
        ]);
    }

    /**
     * Get time summary for a ticket
     */
    public function timeSummary(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        if (! $ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Cannot view this ticket.'], 403);
        }

        $timeEntries = $ticket->timeEntries()
            ->where('status', 'approved')
            ->get();

        $summary = [
            'total_time' => $timeEntries->sum('duration'),
            'billable_time' => $timeEntries->where('billable', true)->sum('duration'),
            'total_amount' => $timeEntries->where('billable', true)->sum('billable_amount'),
            'entries_count' => $timeEntries->count(),
        ];

        return response()->json(['data' => $summary]);
    }

    /**
     * Get activity timeline for a ticket
     */
    public function activity(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        if (! $ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Cannot view this ticket.'], 403);
        }

        $activities = collect();

        // Get ticket creation activity
        $activities->push([
            'id' => 'ticket-created',
            'type' => 'creation',
            'description' => 'Ticket was created',
            'created_at' => $ticket->created_at,
            'user' => $ticket->customer ? [
                'id' => $ticket->customer->id,
                'name' => $ticket->customer->name,
            ] : null,
            'details' => [
                'priority' => $ticket->priority,
                'status' => $ticket->status,
            ],
        ]);

        // Get comments as activities
        $comments = $ticket->comments()
            ->with('user:id,name')
            ->get();

        foreach ($comments as $comment) {
            $activities->push([
                'id' => 'comment-'.$comment->id,
                'type' => 'comment',
                'description' => $comment->is_internal ? 'Added internal note' : 'Added comment',
                'created_at' => $comment->created_at,
                'user' => $comment->user ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                ] : null,
                'details' => [
                    'content' => $comment->content,
                    'is_internal' => $comment->is_internal,
                    'has_attachments' => ! empty($comment->attachments),
                    'attachments' => $comment->attachments,
                ],
            ]);
        }

        // Get time entries as activities
        $timeEntries = $ticket->timeEntries()
            ->with('user:id,name')
            ->get();

        foreach ($timeEntries as $timeEntry) {
            $activities->push([
                'id' => 'time-'.$timeEntry->id,
                'type' => 'time_entry',
                'description' => 'Logged time: '.$this->formatDuration($timeEntry->duration),
                'created_at' => $timeEntry->created_at,
                'user' => $timeEntry->user ? [
                    'id' => $timeEntry->user->id,
                    'name' => $timeEntry->user->name,
                ] : null,
                'details' => [
                    'duration' => $timeEntry->duration,
                    'description' => $timeEntry->description,
                    'billable' => $timeEntry->billable,
                    'billable_amount' => $timeEntry->billable_amount,
                ],
            ]);
        }

        // Sort activities by date (newest first)
        $activities = $activities->sortByDesc('created_at')->values();

        // Apply filters
        if ($request->has('type')) {
            $activities = $activities->where('type', $request->type)->values();
        }

        if ($request->has('user_id')) {
            $activities = $activities->where('user.id', $request->user_id)->values();
        }

        // Manual pagination
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 20);
        $total = $activities->count();
        $items = $activities->forPage($page, $perPage)->values();

        return response()->json([
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
        ]);
    }

    private function formatDuration($seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }
        if ($seconds < 3600) {
            return floor($seconds / 60).'m';
        }

        return floor($seconds / 3600).'h '.floor(($seconds % 3600) / 60).'m';
    }

    /**
     * Get activity statistics for a ticket
     */
    public function activityStats(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        if (! $ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Cannot view this ticket.'], 403);
        }

        // Get counts from actual data
        $commentsCount = $ticket->comments()->count();
        $timeEntriesCount = $ticket->timeEntries()->count();

        // Get unique participants (users who have interacted with the ticket)
        $commentUsers = $ticket->comments()->pluck('user_id')->unique();
        $timeEntryUsers = $ticket->timeEntries()->pluck('user_id')->unique();
        $allParticipants = $commentUsers->merge($timeEntryUsers)->unique();

        // Add ticket creator if exists
        if ($ticket->customer_id) {
            $allParticipants->push($ticket->customer_id);
        }

        $stats = [
            'total_activities' => 1 + $commentsCount + $timeEntriesCount, // +1 for ticket creation
            'comments_count' => $commentsCount,
            'internal_comments_count' => $ticket->comments()->where('is_internal', true)->count(),
            'time_entries_count' => $timeEntriesCount,
            'total_time_logged' => $ticket->timeEntries()->sum('duration'),
            'participants_count' => $allParticipants->filter()->count(),
            'last_activity' => $this->getLastActivityDate($ticket),
            'activity_types' => [
                'ticket_created' => 1,
                'comment' => $ticket->comments()->where('is_internal', false)->count(),
                'internal_comment' => $ticket->comments()->where('is_internal', true)->count(),
                'time_logged' => $timeEntriesCount,
            ],
        ];

        return response()->json(['data' => $stats]);
    }

    private function getLastActivityDate(Ticket $ticket)
    {
        $dates = collect([
            $ticket->created_at,
            $ticket->updated_at,
        ]);

        // Add last comment date
        $lastComment = $ticket->comments()->latest()->first();
        if ($lastComment) {
            $dates->push($lastComment->created_at);
        }

        // Add last time entry date
        $lastTimeEntry = $ticket->timeEntries()->latest()->first();
        if ($lastTimeEntry) {
            $dates->push($lastTimeEntry->created_at);
        }

        return $dates->max();
    }

    /**
     * Get billing summary for a ticket
     */
    public function billingSummary(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        if (! $ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Cannot view this ticket.'], 403);
        }

        $timeEntries = $ticket->timeEntries()->where('billable', true)->get();
        $addons = $ticket->addons()->where('status', 'approved')->get();

        $summary = [
            'total_billable' => $timeEntries->sum('billable_amount') + $addons->sum('actual_cost'),
            'total_invoiced' => $timeEntries->whereNotNull('invoice_id')->sum('billable_amount'),
            'outstanding_amount' => $timeEntries->whereNull('invoice_id')->sum('billable_amount'),
            'billable_hours' => $timeEntries->sum('duration') / 3600,
        ];

        return response()->json(['data' => $summary]);
    }

    /**
     * Get billing rate for a ticket
     */
    public function getBillingRate(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        if (! $ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Cannot view this ticket.'], 403);
        }

        $billingRate = $ticket->billingRate;

        return response()->json(['data' => $billingRate]);
    }

    /**
     * Set billing rate for a ticket
     */
    public function setBillingRate(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        if (! $ticket->canBeEditedBy($user)) {
            return response()->json(['error' => 'Cannot edit this ticket.'], 403);
        }

        $validated = $request->validate([
            'billing_rate_id' => 'required|exists:billing_rates,id',
            'effective_date' => 'nullable|date',
            'apply_to_future' => 'boolean',
            'apply_retroactive' => 'boolean',
        ]);

        $ticket->update(['billing_rate_id' => $validated['billing_rate_id']]);

        // Apply to existing time entries if requested
        if ($validated['apply_retroactive'] ?? false) {
            $ticket->timeEntries()
                ->whereNull('invoice_id')
                ->update(['billing_rate_id' => $validated['billing_rate_id']]);
        }

        return response()->json(['message' => 'Billing rate updated successfully']);
    }

    /**
     * Get invoices related to a ticket
     */
    public function getInvoices(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        if (! $ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Cannot view this ticket.'], 403);
        }

        // Get invoices that contain time entries for this ticket
        $invoiceIds = \DB::table('invoice_line_items')
            ->join('time_entries', 'invoice_line_items.time_entry_id', '=', 'time_entries.id')
            ->where('time_entries.ticket_id', $ticket->id)
            ->whereNotNull('invoice_line_items.time_entry_id')
            ->distinct()
            ->pluck('invoice_line_items.invoice_id');

        // Also get invoices that contain ticket addons for this ticket
        $ticketAddonInvoiceIds = \DB::table('invoice_line_items')
            ->join('ticket_addons', 'invoice_line_items.ticket_addon_id', '=', 'ticket_addons.id')
            ->where('ticket_addons.ticket_id', $ticket->id)
            ->whereNotNull('invoice_line_items.ticket_addon_id')
            ->distinct()
            ->pluck('invoice_line_items.invoice_id');

        // Combine and get unique invoice IDs
        $allInvoiceIds = $invoiceIds->merge($ticketAddonInvoiceIds)->unique();

        if ($allInvoiceIds->isEmpty()) {
            return response()->json(['data' => []]);
        }

        // Get the actual invoices with account information
        $invoices = \App\Models\Invoice::whereIn('id', $allInvoiceIds)
            ->with(['account:id,name', 'user:id,name'])
            ->orderBy('invoice_date', 'desc')
            ->get();

        return response()->json(['data' => $invoices]);
    }

    /**
     * Generate billing report for a ticket
     */
    public function billingReport(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        if (! $ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Cannot view this ticket.'], 403);
        }

        // This would generate a PDF report
        // For now, return success response
        return response()->json(['message' => 'Billing report generated']);
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        if (! $ticket->canBeEditedBy($user)) {
            return response()->json(['error' => 'Cannot edit this ticket.'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:open,in_progress,waiting_customer,on_hold,resolved,closed,cancelled',
            'status_change_reason' => 'nullable|string|max:1000',
            'resolution_type' => 'nullable|string',
            'resolution_summary' => 'nullable|string|max:2000',
            'notify_assigned_user' => 'boolean',
            'notify_customer' => 'boolean',
            'send_status_email' => 'boolean',
        ]);

        $oldStatus = $ticket->status;
        $ticket->update([
            'status' => $validated['status'],
            'resolution_type' => $validated['resolution_type'] ?? null,
            'resolution_summary' => $validated['resolution_summary'] ?? null,
            'status_changed_at' => now(),
        ]);

        // Log activity
        $ticket->activities()->create([
            'user_id' => $user->id,
            'type' => 'status_change',
            'description' => "Status changed from {$oldStatus} to {$validated['status']}",
            'details' => [
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'reason' => $validated['status_change_reason'] ?? null,
            ],
        ]);

        return response()->json(['message' => 'Status updated successfully']);
    }

    /**
     * Update ticket assignment
     */
    public function updateAssignment(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        if (! $ticket->canBeEditedBy($user)) {
            return response()->json(['error' => 'Cannot edit this ticket.'], 403);
        }

        $validated = $request->validate([
            'agent_id' => 'nullable|exists:users,id',
            'assignment_reason' => 'nullable|string|max:1000',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'notify_new_assignee' => 'boolean',
            'notify_previous_assignee' => 'boolean',
            'notify_customer' => 'boolean',
        ]);

        $oldAssignee = $ticket->assignedTo;

        $updateData = ['agent_id' => $validated['agent_id']];
        if ($validated['priority'] ?? false) {
            $updateData['priority'] = $validated['priority'];
        }

        $ticket->update($updateData);

        // Log activity
        $ticket->activities()->create([
            'user_id' => $user->id,
            'type' => 'assignment',
            'description' => $validated['agent_id']
                ? 'Assigned to '.User::find($validated['agent_id'])->name
                : 'Unassigned',
            'details' => [
                'old_assignee' => $oldAssignee?->name,
                'new_assignee' => $validated['agent_id'] ? User::find($validated['agent_id'])->name : null,
                'reason' => $validated['assignment_reason'] ?? null,
            ],
        ]);

        return response()->json(['message' => 'Assignment updated successfully']);
    }

    /**
     * Get available ticket statuses
     */
    public function getStatuses(Request $request): JsonResponse
    {
        $statuses = [
            ['value' => 'open', 'label' => 'Open'],
            ['value' => 'in_progress', 'label' => 'In Progress'],
            ['value' => 'waiting_customer', 'label' => 'Waiting for Customer'],
            ['value' => 'on_hold', 'label' => 'On Hold'],
            ['value' => 'resolved', 'label' => 'Resolved'],
            ['value' => 'closed', 'label' => 'Closed'],
            ['value' => 'cancelled', 'label' => 'Cancelled'],
        ];

        return response()->json(['data' => $statuses]);
    }

    /**
     * Get related tickets for a specific ticket
     */
    public function relatedTickets(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        // Check permissions
        if (! $ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Find related tickets based on:
        // 1. Same account
        // 2. Same customer
        // 3. Similar keywords in title/description
        $relatedTickets = Ticket::query()
            ->with(['account:id,name'])
            ->where('id', '!=', $ticket->id) // Exclude current ticket
            ->where(function ($query) use ($ticket) {
                // Same account tickets
                $query->where('account_id', $ticket->account_id);

                // Same customer tickets (if customer_id exists)
                if ($ticket->customer_id) {
                    $query->orWhere('customer_id', $ticket->customer_id);
                }

                // Similar title tickets (basic keyword matching)
                if ($ticket->title) {
                    $keywords = array_filter(explode(' ', $ticket->title), function ($word) {
                        return strlen($word) > 3; // Only words longer than 3 chars
                    });

                    foreach (array_slice($keywords, 0, 3) as $keyword) { // Limit to 3 keywords
                        $query->orWhere('title', 'LIKE', '%'.$keyword.'%');
                    }
                }
            })
            ->limit(10) // Limit to 10 related tickets
            ->get()
            ->map(function ($relatedTicket) {
                return [
                    'id' => $relatedTicket->id,
                    'ticket_number' => $relatedTicket->ticket_number,
                    'title' => $relatedTicket->title,
                    'status' => $relatedTicket->status,
                    'priority' => $relatedTicket->priority,
                    'created_at' => $relatedTicket->created_at,
                    'account' => $relatedTicket->account ? [
                        'id' => $relatedTicket->account->id,
                        'name' => $relatedTicket->account->name,
                    ] : null,
                ];
            });

        return response()->json(['data' => $relatedTickets]);
    }
}
