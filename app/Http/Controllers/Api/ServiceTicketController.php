<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceTicket;
use App\Models\Account;
use App\Models\User;
use App\Models\BillingRate;
use App\Http\Resources\ServiceTicketResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ServiceTicketController extends Controller
{
    /**
     * Display a listing of service tickets with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Build base query with user's accessible accounts
        $query = ServiceTicket::with([
            'account:id,name', 
            'createdBy:id,name', 
            'assignedTo:id,name', 
            'billingRate:id,rate,currency'
        ]);
        
        // Apply user scope - employees see assigned tickets, managers/admins see team tickets
        if ($user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            // Admins see all tickets in their accounts
            $accountIds = $user->accounts()->pluck('accounts.id');
            $query->whereIn('account_id', $accountIds);
        } elseif ($user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists()) {
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
                  ->orWhere('assigned_to', $user->id)
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
            $q->where('assigned_to', $assignedTo);
        })->when($request->account_id, function ($q, $accountId) {
            $q->where('account_id', $accountId);
        })->when($request->overdue, function ($q) {
            $q->overdue();
        })->when($request->search, function ($q, $search) {
            $q->where(function ($searchQuery) use ($search) {
                $searchQuery->where('title', 'like', "%{$search}%")
                           ->orWhere('description', 'like', "%{$search}%")
                           ->orWhere('ticket_number', 'like', "%{$search}%")
                           ->orWhere('customer_name', 'like', "%{$search}%")
                           ->orWhere('customer_email', 'like', "%{$search}%");
            });
        });
        
        // Apply sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        
        // Handle special sorting cases
        if ($sortField === 'priority') {
            $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')");
        } else {
            $query->orderBy($sortField, $sortDirection);
        }
        
        // Get paginated results
        $tickets = $query->paginate($request->input('per_page', 20));
        
        // Add summary statistics
        $stats = [
            'total' => $tickets->total(),
            'open_count' => (clone $query->getQuery())->whereIn('status', [
                ServiceTicket::STATUS_OPEN, 
                ServiceTicket::STATUS_IN_PROGRESS, 
                ServiceTicket::STATUS_WAITING_CUSTOMER
            ])->count(),
            'overdue_count' => (clone $query->getQuery())->overdue()->count(),
            'urgent_count' => (clone $query->getQuery())->where('priority', ServiceTicket::PRIORITY_URGENT)->count(),
        ];
        
        return response()->json([
            'data' => ServiceTicketResource::collection($tickets),
            'meta' => array_merge($tickets->toArray(), ['stats' => $stats])
        ]);
    }

    /**
     * Store a newly created service ticket
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'account_id' => 'required|exists:accounts,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'category' => ['required', Rule::in(['support', 'maintenance', 'development', 'consulting', 'other'])],
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date|after:now',
            'estimated_hours' => 'nullable|integer|min:1|max:1000',
            'billable' => 'boolean',
            'billing_rate_id' => 'nullable|exists:billing_rates,id',
            'quoted_amount' => 'nullable|numeric|min:0|max:999999.99',
            'requires_approval' => 'boolean',
            'internal_notes' => 'nullable|string|max:5000',
        ]);
        
        // Verify user has access to the account
        if (!$user->accounts()->where('accounts.id', $validated['account_id'])->exists()) {
            return response()->json(['error' => 'You do not have access to this account.'], 403);
        }
        
        // Verify assigned user has access to the account if specified
        if (!empty($validated['assigned_to'])) {
            $assignedUser = User::find($validated['assigned_to']);
            if (!$assignedUser->accounts()->where('accounts.id', $validated['account_id'])->exists()) {
                return response()->json(['error' => 'Assigned user does not have access to this account.'], 422);
            }
        }
        
        // Create the ticket
        $ticket = ServiceTicket::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'account_id' => $validated['account_id'],
            'customer_name' => $validated['customer_name'] ?? null,
            'customer_email' => $validated['customer_email'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'priority' => $validated['priority'],
            'category' => $validated['category'],
            'created_by' => $user->id,
            'assigned_to' => $validated['assigned_to'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'billable' => $validated['billable'] ?? true,
            'billing_rate_id' => $validated['billing_rate_id'] ?? null,
            'quoted_amount' => $validated['quoted_amount'] ?? null,
            'requires_approval' => $validated['requires_approval'] ?? false,
            'internal_notes' => $validated['internal_notes'] ?? null,
            'status' => $validated['assigned_to'] ? ServiceTicket::STATUS_IN_PROGRESS : ServiceTicket::STATUS_OPEN,
        ]);
        
        $ticket->load(['account:id,name', 'createdBy:id,name', 'assignedTo:id,name']);
        
        return response()->json([
            'data' => new ServiceTicketResource($ticket),
            'message' => 'Service ticket created successfully.'
        ], 201);
    }

    /**
     * Display the specified service ticket
     */
    public function show(Request $request, ServiceTicket $serviceTicket): JsonResponse
    {
        $user = $request->user();
        
        // Check if user can view this ticket
        if (!$serviceTicket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }
        
        $serviceTicket->load([
            'account:id,name', 
            'createdBy:id,name,email', 
            'assignedTo:id,name,email',
            'assignedUsers:id,name,email',
            'billingRate:id,rate,currency',
            'timeEntries:id,duration,billable,created_at',
            'timers:id,status,started_at'
        ]);
        
        return response()->json([
            'data' => new ServiceTicketResource($serviceTicket)
        ]);
    }

    /**
     * Update the specified service ticket
     */
    public function update(Request $request, ServiceTicket $serviceTicket): JsonResponse
    {
        $user = $request->user();
        
        // Check if user can edit this ticket
        if (!$serviceTicket->canBeEditedBy($user)) {
            return response()->json(['error' => 'Cannot edit this ticket.'], 403);
        }
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:10000',
            'customer_name' => 'sometimes|nullable|string|max:255',
            'customer_email' => 'sometimes|nullable|email|max:255',
            'customer_phone' => 'sometimes|nullable|string|max:20',
            'priority' => ['sometimes', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'category' => ['sometimes', Rule::in(['support', 'maintenance', 'development', 'consulting', 'other'])],
            'assigned_to' => 'sometimes|nullable|exists:users,id',
            'due_date' => 'sometimes|nullable|date|after:now',
            'estimated_hours' => 'sometimes|nullable|integer|min:1|max:1000',
            'billable' => 'sometimes|boolean',
            'billing_rate_id' => 'sometimes|nullable|exists:billing_rates,id',
            'quoted_amount' => 'sometimes|nullable|numeric|min:0|max:999999.99',
            'requires_approval' => 'sometimes|boolean',
            'internal_notes' => 'sometimes|nullable|string|max:5000',
            'resolution_notes' => 'sometimes|nullable|string|max:5000',
        ]);
        
        // Verify assigned user has access to the account if specified
        if (isset($validated['assigned_to']) && $validated['assigned_to']) {
            $assignedUser = User::find($validated['assigned_to']);
            if (!$assignedUser->accounts()->where('accounts.id', $serviceTicket->account_id)->exists()) {
                return response()->json(['error' => 'Assigned user does not have access to this account.'], 422);
            }
        }
        
        $serviceTicket->update($validated);
        $serviceTicket->load(['account:id,name', 'createdBy:id,name', 'assignedTo:id,name']);
        
        return response()->json([
            'data' => new ServiceTicketResource($serviceTicket),
            'message' => 'Service ticket updated successfully.'
        ]);
    }

    /**
     * Remove the specified service ticket
     */
    public function destroy(Request $request, ServiceTicket $serviceTicket): JsonResponse
    {
        $user = $request->user();
        
        // Only admins can delete tickets
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            return response()->json(['error' => 'Only administrators can delete tickets.'], 403);
        }
        
        // Check account access
        if (!$user->accounts()->where('accounts.id', $serviceTicket->account_id)->exists()) {
            return response()->json(['error' => 'You do not have access to this account.'], 403);
        }
        
        $serviceTicket->delete();
        
        return response()->json([
            'message' => 'Service ticket deleted successfully.'
        ]);
    }
    
    /**
     * Workflow Actions
     */
    
    /**
     * Transition ticket to a new status
     */
    public function transitionStatus(Request $request, ServiceTicket $serviceTicket): JsonResponse
    {
        $user = $request->user();
        
        if (!$serviceTicket->canBeEditedBy($user)) {
            return response()->json(['error' => 'Cannot modify this ticket.'], 403);
        }
        
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                ServiceTicket::STATUS_OPEN,
                ServiceTicket::STATUS_IN_PROGRESS,
                ServiceTicket::STATUS_WAITING_CUSTOMER,
                ServiceTicket::STATUS_RESOLVED,
                ServiceTicket::STATUS_CLOSED,
                ServiceTicket::STATUS_CANCELLED
            ])],
            'notes' => 'nullable|string|max:2000'
        ]);
        
        if (!$serviceTicket->canTransitionTo($validated['status'])) {
            return response()->json([
                'error' => "Cannot transition from {$serviceTicket->status} to {$validated['status']}."
            ], 422);
        }
        
        $success = $serviceTicket->transitionTo($validated['status'], $user, $validated['notes'] ?? null);
        
        if (!$success) {
            return response()->json(['error' => 'Failed to update ticket status.'], 500);
        }
        
        $serviceTicket->load(['account:id,name', 'createdBy:id,name', 'assignedTo:id,name']);
        
        return response()->json([
            'data' => new ServiceTicketResource($serviceTicket),
            'message' => "Ticket status updated to {$validated['status']}."
        ]);
    }
    
    /**
     * Assign ticket to a user
     */
    public function assign(Request $request, ServiceTicket $serviceTicket): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() &&
            !$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            return response()->json(['error' => 'Insufficient permissions to assign tickets.'], 403);
        }
        
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);
        
        $assignedUser = User::find($validated['assigned_to']);
        
        // Verify assigned user has access to the account
        if (!$assignedUser->accounts()->where('accounts.id', $serviceTicket->account_id)->exists()) {
            return response()->json(['error' => 'User does not have access to this account.'], 422);
        }
        
        $success = $serviceTicket->assignTo($assignedUser);
        
        if (!$success) {
            return response()->json(['error' => 'Failed to assign ticket.'], 500);
        }
        
        $serviceTicket->load(['account:id,name', 'createdBy:id,name', 'assignedTo:id,name']);
        
        return response()->json([
            'data' => new ServiceTicketResource($serviceTicket),
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
        $query = ServiceTicket::query();
        
        if ($user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            $accountIds = $user->accounts()->pluck('accounts.id');
            $query->whereIn('account_id', $accountIds);
        } elseif ($user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists()) {
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
                  ->orWhere('assigned_to', $user->id);
            });
        }
        
        $stats = [
            'total' => (clone $query)->count(),
            'open' => (clone $query)->where('status', ServiceTicket::STATUS_OPEN)->count(),
            'in_progress' => (clone $query)->where('status', ServiceTicket::STATUS_IN_PROGRESS)->count(),
            'waiting_customer' => (clone $query)->where('status', ServiceTicket::STATUS_WAITING_CUSTOMER)->count(),
            'resolved' => (clone $query)->where('status', ServiceTicket::STATUS_RESOLVED)->count(),
            'closed' => (clone $query)->where('status', ServiceTicket::STATUS_CLOSED)->count(),
            'overdue' => (clone $query)->overdue()->count(),
            'high_priority' => (clone $query)->whereIn('priority', [ServiceTicket::PRIORITY_HIGH, ServiceTicket::PRIORITY_URGENT])->count(),
            'assigned_to_me' => (clone $query)->where('assigned_to', $user->id)->count(),
        ];
        
        return response()->json(['data' => $stats]);
    }
    
    /**
     * Get tickets assigned to the current user
     */
    public function myTickets(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = ServiceTicket::with(['account:id,name', 'createdBy:id,name'])
            ->where('assigned_to', $user->id);
        
        // Apply status filter if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to open tickets
            $query->open();
        }
        
        $tickets = $query->orderBy('priority', 'desc')
                        ->orderBy('due_date', 'asc')
                        ->paginate($request->input('per_page', 10));
        
        return response()->json([
            'data' => ServiceTicketResource::collection($tickets),
            'meta' => $tickets->toArray()
        ]);
    }
    
    /**
     * Team Assignment Actions
     */
    
    /**
     * Add a user to the ticket team
     */
    public function addTeamMember(Request $request, ServiceTicket $serviceTicket): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() &&
            !$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            return response()->json(['error' => 'Insufficient permissions to manage ticket teams.'], 403);
        }
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'assignment_notes' => 'nullable|string|max:500'
        ]);
        
        $teamMember = User::find($validated['user_id']);
        
        // Verify team member has access to the account
        if (!$teamMember->accounts()->where('accounts.id', $serviceTicket->account_id)->exists()) {
            return response()->json(['error' => 'User does not have access to this account.'], 422);
        }
        
        // Check if already assigned
        if ($serviceTicket->assignedUsers()->where('users.id', $teamMember->id)->exists()) {
            return response()->json(['error' => 'User is already assigned to this ticket.'], 422);
        }
        
        $serviceTicket->assignedUsers()->attach($teamMember->id, [
            'assigned_by' => $user->id,
            'assigned_at' => now(),
            'assignment_notes' => $validated['assignment_notes'] ?? null
        ]);
        
        $serviceTicket->load(['assignedUsers:id,name,email']);
        
        return response()->json([
            'data' => new ServiceTicketResource($serviceTicket),
            'message' => "Added {$teamMember->name} to ticket team."
        ]);
    }
    
    /**
     * Remove a user from the ticket team
     */
    public function removeTeamMember(Request $request, ServiceTicket $serviceTicket, User $teamMember): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() &&
            !$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            return response()->json(['error' => 'Insufficient permissions to manage ticket teams.'], 403);
        }
        
        // Check if user is assigned to this ticket
        if (!$serviceTicket->assignedUsers()->where('users.id', $teamMember->id)->exists()) {
            return response()->json(['error' => 'User is not assigned to this ticket.'], 422);
        }
        
        $serviceTicket->assignedUsers()->detach($teamMember->id);
        $serviceTicket->load(['assignedUsers:id,name,email']);
        
        return response()->json([
            'data' => new ServiceTicketResource($serviceTicket),
            'message' => "Removed {$teamMember->name} from ticket team."
        ]);
    }
    
    /**
     * Get ticket team members with assignment details
     */
    public function getTeamMembers(Request $request, ServiceTicket $serviceTicket): JsonResponse
    {
        $user = $request->user();
        
        // Check if user can view this ticket
        if (!$serviceTicket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }
        
        $teamMembers = $serviceTicket->assignedUsers()
            ->withPivot(['assigned_by', 'assigned_at', 'assignment_notes'])
            ->with(['assignedBy:id,name'])
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'assigned_by' => [
                        'id' => $member->pivot->assignedBy->id,
                        'name' => $member->pivot->assignedBy->name
                    ],
                    'assigned_at' => $member->pivot->assigned_at,
                    'assignment_notes' => $member->pivot->assignment_notes
                ];
            });
        
        return response()->json([
            'data' => $teamMembers
        ]);
    }
    
    /**
     * Bulk assign multiple users to a ticket
     */
    public function bulkAssignTeam(Request $request, ServiceTicket $serviceTicket): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() &&
            !$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            return response()->json(['error' => 'Insufficient permissions to manage ticket teams.'], 403);
        }
        
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1|max:10',
            'user_ids.*' => 'exists:users,id',
            'assignment_notes' => 'nullable|string|max:500'
        ]);
        
        $users = User::whereIn('id', $validated['user_ids'])->get();
        
        // Verify all users have access to the account
        $invalidUsers = $users->filter(function ($member) use ($serviceTicket) {
            return !$member->accounts()->where('accounts.id', $serviceTicket->account_id)->exists();
        });
        
        if ($invalidUsers->isNotEmpty()) {
            return response()->json([
                'error' => 'Some users do not have access to this account.',
                'invalid_users' => $invalidUsers->pluck('name')
            ], 422);
        }
        
        // Check for already assigned users
        $alreadyAssigned = $serviceTicket->assignedUsers()
            ->whereIn('users.id', $validated['user_ids'])
            ->pluck('users.id')
            ->toArray();
        
        $newAssignments = array_diff($validated['user_ids'], $alreadyAssigned);
        
        if (empty($newAssignments)) {
            return response()->json(['error' => 'All selected users are already assigned to this ticket.'], 422);
        }
        
        // Bulk assign new team members
        $attachData = [];
        foreach ($newAssignments as $userId) {
            $attachData[$userId] = [
                'assigned_by' => $user->id,
                'assigned_at' => now(),
                'assignment_notes' => $validated['assignment_notes'] ?? null
            ];
        }
        
        $serviceTicket->assignedUsers()->attach($attachData);
        $serviceTicket->load(['assignedUsers:id,name,email']);
        
        $assignedCount = count($newAssignments);
        $skippedCount = count($alreadyAssigned);
        
        $message = "Assigned {$assignedCount} team members to ticket.";
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} were already assigned)";
        }
        
        return response()->json([
            'data' => new ServiceTicketResource($serviceTicket),
            'message' => $message,
            'assigned_count' => $assignedCount,
            'skipped_count' => $skippedCount
        ]);
    }
}