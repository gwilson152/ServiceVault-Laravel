<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TimeEntryResource;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeEntryController extends Controller
{
    /**
     * Display a listing of time entries with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Build base query with user's accessible accounts and all necessary relationships
        $query = TimeEntry::with([
            'user:id,name',
            'account:id,name',
            'ticket:id,title,account_id',
            'billingRate:id,rate',
        ]);

        // Apply user scope - employees see their own, service providers/managers/admins see team members
        if ($user->hasAnyPermission(['admin.manage', 'admin.write'])) {
            // Admins see all time entries
        } elseif ($user->user_type === 'service_provider' ||
                  $user->hasAnyPermission(['time.manage', 'time.view.all', 'teams.manage'])) {
            // Service providers and managers see entries for accounts they manage
            $managedAccountIds = $user->accounts()
                ->whereHas('users', function ($userQuery) use ($user) {
                    $userQuery->where('users.id', $user->id);
                })
                ->pluck('accounts.id');
            $query->whereIn('account_id', $managedAccountIds);
        } elseif ($user->hasPermission('time.view.team')) {
            // Team members can see team entries but filtered by their team accounts
            $teamAccountIds = $user->teamAccounts()->pluck('accounts.id');
            $query->whereIn('account_id', $teamAccountIds);
        } else {
            // Regular employees see only their own entries
            $query->where('user_id', $user->id);
        }

        // Apply filters
        $query->when($request->status, function ($q, $status) {
            $q->where('status', $status);
        })->when($request->user_id, function ($q, $userId) use ($user) {
            // Service providers, managers, and admins can filter by user
            if ($user->user_type === 'service_provider' ||
                $user->hasAnyPermission(['time.manage', 'time.view.all', 'teams.manage', 'admin.manage', 'admin.write'])) {
                $q->where('user_id', $userId);
            }
        })->when($request->account_id, function ($q, $accountId) {
            $q->where('account_id', $accountId);
        })->when($request->date_from, function ($q, $dateFrom) {
            $q->whereDate('started_at', '>=', $dateFrom);
        })->when($request->date_to, function ($q, $dateTo) {
            $q->whereDate('started_at', '<=', $dateTo);
        })->when($request->billable !== null, function ($q) use ($request) {
            $q->where('billable', $request->boolean('billable'));
        });

        // Apply sorting
        $sortField = $request->input('sort', 'started_at');
        $sortDirection = $request->input('direction', 'desc');

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['started_at', 'ended_at', 'duration', 'created_at', 'updated_at', 'status'];
        if (! in_array($sortField, $allowedSortFields)) {
            $sortField = 'started_at';
        }

        $query->orderBy($sortField, $sortDirection);

        // Get paginated results
        $timeEntries = $query->paginate($request->input('per_page', 20));

        // Add summary statistics
        $stats = [
            'total_hours' => $timeEntries->sum(function ($entry) {
                return $entry->duration / 60; // Duration is stored in minutes
            }),
            'billable_hours' => $timeEntries->where('billable', true)->sum(function ($entry) {
                return $entry->duration / 60; // Duration is stored in minutes
            }),
            'pending_count' => $timeEntries->where('status', 'pending')->count(),
            'approved_count' => $timeEntries->where('status', 'approved')->count(),
        ];

        return response()->json([
            'data' => TimeEntryResource::collection($timeEntries),
            'meta' => array_merge($timeEntries->toArray(), ['stats' => $stats]),
        ]);
    }

    /**
     * Store a newly created time entry
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Validate that user is an Agent (can create time entries)
        try {
            TimeEntry::validateUserCanCreateTimeEntry($user);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }

        $validated = $request->validate([
            'description' => 'required|string|max:1000',
            'duration' => 'required|integer|min:1|max:1440', // 1 minute to 24 hours (in minutes)
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after:started_at',
            'account_id' => 'required|exists:accounts,id',
            'ticket_id' => 'nullable|exists:tickets,id',
            'user_id' => 'nullable|exists:users,id', // Allow assigning to different users (for agents/admins)
            'billable' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'billing_rate_id' => 'nullable|exists:billing_rates,id',
            'rate_override' => 'nullable|numeric|min:0|max:9999.99', // Allow rate override for time managers/admins
            'timer_id' => 'nullable|exists:timers,id', // For timer commit functionality
        ]);

        // Validate time entry data for Agent/Customer architecture compliance
        try {
            TimeEntry::validateTimeEntryData($validated);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // Verify user has permission to log time for this account
        $accessibleAccountIds = $user->getAccessibleAccountIds('time.create');
        if (! in_array($validated['account_id'], $accessibleAccountIds)) {
            return response()->json(['error' => 'You do not have permission to log time for this account.'], 403);
        }

        // Determine who performed the work - use provided user_id or default to current user
        $assignedUserId = $validated['user_id'] ?? $user->id;

        // Verify the assigned user can be a time entry agent (if different from current user)
        if ($assignedUserId !== $user->id) {
            $assignedUser = User::find($assignedUserId);
            if (! $assignedUser || ! $assignedUser->hasAnyPermission(['time.act_as_agent', 'time.track', 'time.manage', 'admin.write'])) {
                return response()->json(['error' => 'The assigned user cannot be a time entry agent.'], 403);
            }
        }

        // Handle timer commit if timer_id is provided
        $timer = null;
        if (isset($validated['timer_id'])) {
            $timer = \App\Models\Timer::find($validated['timer_id']);
            if ($timer) {
                // Verify user owns the timer or has permission to manage it
                if ($timer->user_id !== $user->id && ! $user->hasAnyPermission(['timers.manage', 'admin.write'])) {
                    return response()->json(['error' => 'You do not have permission to commit this timer.'], 403);
                }

                // Verify timer is in a committable state
                if ($timer->status === 'committed') {
                    return response()->json(['error' => 'Timer has already been committed.'], 422);
                }

                // Commit the timer (changes status to 'committed' and sets stopped_at)
                $timer->commit();
            }
        }

        // Check if user can override rates (time managers and admins)
        $canOverrideRates = $user->hasAnyPermission(['time.manage', 'admin.manage']);
        $rateOverride = null;

        if ($canOverrideRates && isset($validated['rate_override'])) {
            $rateOverride = $validated['rate_override'];
        }

        // Get billing rate value for rate_at_time field (audit trail)
        $rateAtTime = null;
        if (isset($validated['billing_rate_id']) && $validated['billing_rate_id']) {
            $billingRate = \App\Models\BillingRate::find($validated['billing_rate_id']);
            if ($billingRate) {
                $rateAtTime = $billingRate->rate;
            }
        }

        // Create time entry with Agent/Customer architecture
        $timeEntry = TimeEntry::create([
            'user_id' => $assignedUserId, // Agent who performed the work
            'description' => $validated['description'],
            'duration' => intval($validated['duration']), // Duration is already in minutes
            'started_at' => $validated['started_at'],
            'ended_at' => $validated['ended_at'] ?? Carbon::parse($validated['started_at'])->addMinutes($validated['duration']),
            'account_id' => $validated['account_id'], // Customer account (always required)
            'ticket_id' => $validated['ticket_id'] ?? null, // Optional ticket association
            'billable' => $validated['billable'] ?? true,
            'notes' => $validated['notes'] ?? null,
            'billing_rate_id' => $validated['billing_rate_id'] ?? null,
            'rate_at_time' => $rateAtTime, // Store billing rate at time of creation for audit
            'rate_override' => $rateOverride,
            'status' => 'pending', // Default status for approval workflow
        ]);

        // Link the timer to the time entry if this was a timer commit
        if ($timer) {
            $timer->time_entry_id = $timeEntry->id;
            $timer->save();
        }

        $timeEntry->load(['user:id,name', 'account:id,name', 'ticket:id,title']);

        return response()->json([
            'data' => new TimeEntryResource($timeEntry),
            'message' => 'Time entry created successfully.',
        ], 201);
    }

    /**
     * Display the specified time entry
     */
    public function show(Request $request, TimeEntry $timeEntry): JsonResponse
    {
        $user = $request->user();

        // Check if user can view this time entry
        if ($timeEntry->user_id !== $user->id &&
            ! $user->hasAnyPermission(['teams.manage', 'admin.manage'])) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $timeEntry->load(['user:id,name,email', 'account:id,name', 'billingRate']);

        return response()->json([
            'data' => new TimeEntryResource($timeEntry),
        ]);
    }

    /**
     * Update the specified time entry
     */
    public function update(Request $request, TimeEntry $timeEntry): JsonResponse
    {
        $user = $request->user();

        // Check permissions - users can edit their own pending entries, service providers/managers can edit team entries
        $canEdit = false;

        // Only pending entries can be edited
        if ($timeEntry->status !== 'pending') {
            return response()->json(['error' => 'Can only edit pending time entries.'], 403);
        }

        // Original creator can always edit their own pending entries
        if ($timeEntry->user_id === $user->id) {
            $canEdit = true;
        } elseif ($user->user_type === 'service_provider' ||
                  $user->hasAnyPermission(['time.manage', 'time.edit.all', 'admin.manage', 'admin.write'])) {
            // Service providers and users with time management permissions can edit time entries
            $canEdit = true;
        } elseif ($user->hasPermission('time.edit.team') || $user->hasPermission('teams.manage')) {
            // Team managers can edit entries from their team members
            $canEdit = true;
        }

        if (! $canEdit) {
            return response()->json(['error' => 'Cannot edit this time entry.'], 403);
        }

        $validated = $request->validate([
            'description' => 'sometimes|string|max:1000',
            'duration' => 'sometimes|integer|min:1|max:1440',
            'date' => 'sometimes|date|before_or_equal:today',
            'billable' => 'sometimes|boolean',
            'notes' => 'sometimes|nullable|string|max:2000',
            'billing_rate_id' => 'sometimes|nullable|exists:billing_rates,id',
            'rate_override' => 'sometimes|nullable|numeric|min:0|max:9999.99',
        ]);

        // Check if user can override rates (time managers and admins)
        $canOverrideRates = $user->hasAnyPermission(['time.manage', 'admin.manage']);

        if (! $canOverrideRates && isset($validated['rate_override'])) {
            unset($validated['rate_override']); // Remove rate override if user doesn't have permission
        }

        // Update rate_at_time if billing_rate_id is being changed
        if (isset($validated['billing_rate_id']) && $validated['billing_rate_id']) {
            $billingRate = \App\Models\BillingRate::find($validated['billing_rate_id']);
            if ($billingRate) {
                $validated['rate_at_time'] = $billingRate->rate;
            }
        } elseif (isset($validated['billing_rate_id']) && $validated['billing_rate_id'] === null) {
            // If billing rate is being removed, clear rate_at_time
            $validated['rate_at_time'] = null;
        }

        // Reset status to pending if content changed (unless user is manager/admin)
        if (($request->has('description') || $request->has('duration') || $request->has('date')) &&
            $timeEntry->status === 'approved' &&
            $timeEntry->user_id === $user->id &&
            ! $user->hasPermission('teams.manage')) {
            $validated['status'] = 'pending';
        }

        $timeEntry->update($validated);
        $timeEntry->load(['user:id,name', 'account:id,name']);

        return response()->json([
            'data' => new TimeEntryResource($timeEntry),
            'message' => 'Time entry updated successfully.',
        ]);
    }

    /**
     * Remove the specified time entry
     */
    public function destroy(Request $request, TimeEntry $timeEntry): JsonResponse
    {
        $user = $request->user();

        // Check permissions
        $canDelete = false;
        if ($timeEntry->user_id === $user->id && $timeEntry->status === 'pending') {
            $canDelete = true;
        } elseif ($user->hasAnyPermission(['teams.manage', 'admin.manage'])) {
            $canDelete = true;
        }

        if (! $canDelete) {
            return response()->json(['error' => 'Cannot delete this time entry.'], 403);
        }

        $timeEntry->delete();

        return response()->json([
            'message' => 'Time entry deleted successfully.',
        ]);
    }

    /**
     * Approve a time entry (managers/admins only)
     */
    public function approve(Request $request, TimeEntry $timeEntry): JsonResponse
    {
        $user = $request->user();

        // Verify approval permissions - service providers, managers, and admins can approve
        if (! ($user->user_type === 'service_provider' ||
              $user->hasAnyPermission(['time.manage', 'time.approve', 'teams.manage', 'admin.manage', 'admin.write']))) {
            return response()->json(['error' => 'Insufficient permissions to approve time entries.'], 403);
        }

        // Verify manager has access to this account
        if (! $user->hasPermission('admin.manage')) {
            $managedAccountIds = $user->accounts()
                ->whereHas('users', function ($userQuery) use ($user) {
                    $userQuery->where('users.id', $user->id);
                })
                ->pluck('accounts.id');

            if (! $managedAccountIds->contains($timeEntry->account_id)) {
                return response()->json(['error' => 'You cannot approve time entries for this account.'], 403);
            }
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'rate_override' => 'nullable|numeric|min:0|max:9999.99',
        ]);

        // Check if user can override rates (time managers and admins)
        $canOverrideRates = $user->hasAnyPermission(['time.manage', 'admin.manage']);
        $rateOverride = null;

        if ($canOverrideRates && isset($validated['rate_override'])) {
            $rateOverride = $validated['rate_override'];
        }

        // Use the model's approve method which locks the calculated amount
        $timeEntry->approve(
            $user->id,
            $validated['notes'] ?? null,
            $rateOverride
        );

        $timeEntry->load(['user:id,name', 'account:id,name', 'approvedBy:id,name']);

        return response()->json([
            'data' => new TimeEntryResource($timeEntry),
            'message' => 'Time entry approved successfully.',
        ]);
    }

    /**
     * Reject a time entry (managers/admins only)
     */
    public function reject(Request $request, TimeEntry $timeEntry): JsonResponse
    {
        $user = $request->user();

        // Verify rejection permissions - service providers, managers, and admins can reject
        if (! ($user->user_type === 'service_provider' ||
              $user->hasAnyPermission(['time.manage', 'time.approve', 'teams.manage', 'admin.manage', 'admin.write']))) {
            return response()->json(['error' => 'Insufficient permissions to reject time entries.'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $timeEntry->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $validated['reason'],
        ]);

        $timeEntry->load(['user:id,name', 'account:id,name', 'approvedBy:id,name']);

        return response()->json([
            'data' => new TimeEntryResource($timeEntry),
            'message' => 'Time entry rejected successfully.',
        ]);
    }

    /**
     * Unapprove a time entry (only if not invoiced)
     */
    public function unapprove(Request $request, TimeEntry $timeEntry): JsonResponse
    {
        $user = $request->user();

        // Verify unapproval permissions - same as approval permissions
        if (! ($user->user_type === 'service_provider' ||
              $user->hasAnyPermission(['time.manage', 'time.approve', 'teams.manage', 'admin.manage', 'admin.write']))) {
            return response()->json(['error' => 'Insufficient permissions to unapprove time entries.'], 403);
        }

        // Check if the time entry can be unapproved
        if (! $timeEntry->canUnapprove()) {
            if ($timeEntry->isInvoiced()) {
                return response()->json(['error' => 'Cannot unapprove time entry that has been invoiced.'], 422);
            }
            if ($timeEntry->status !== 'approved') {
                return response()->json(['error' => 'Time entry is not approved.'], 422);
            }
        }

        // Verify manager has access to this account
        if (! $user->hasPermission('admin.manage')) {
            $managedAccountIds = $user->accounts()
                ->whereHas('users', function ($userQuery) use ($user) {
                    $userQuery->where('users.id', $user->id);
                })
                ->pluck('accounts.id');

            if (! $managedAccountIds->contains($timeEntry->account_id)) {
                return response()->json(['error' => 'You cannot unapprove time entries for this account.'], 403);
            }
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        // Use the model's unapprove method
        $success = $timeEntry->unapprove(
            $user->id,
            $validated['notes'] ?? 'Unapproved and returned to pending status'
        );

        if (! $success) {
            return response()->json(['error' => 'Failed to unapprove time entry.'], 422);
        }

        $timeEntry->load(['user:id,name', 'account:id,name']);

        return response()->json([
            'data' => new TimeEntryResource($timeEntry),
            'message' => 'Time entry unapproved successfully and returned to pending status.',
        ]);
    }

    /**
     * Bulk approval of time entries
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $user = $request->user();

        // Verify bulk approval permissions - service providers, managers, and admins can bulk approve
        if (! ($user->user_type === 'service_provider' ||
              $user->hasAnyPermission(['time.manage', 'time.approve', 'teams.manage', 'admin.manage', 'admin.write']))) {
            return response()->json(['error' => 'Insufficient permissions for bulk approval.'], 403);
        }

        $validated = $request->validate([
            'time_entry_ids' => 'required|array|min:1|max:100',
            'time_entry_ids.*' => 'exists:time_entries,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Get time entries and verify access
        $timeEntries = TimeEntry::whereIn('id', $validated['time_entry_ids'])
            ->where('status', 'pending')
            ->get();

        if ($timeEntries->isEmpty()) {
            return response()->json(['error' => 'No pending time entries found to approve.'], 422);
        }

        // Verify access to all accounts
        if (! $user->hasPermission('admin.manage')) {
            $managedAccountIds = $user->accounts()
                ->whereHas('users', function ($userQuery) use ($user) {
                    $userQuery->where('users.id', $user->id);
                })
                ->pluck('accounts.id');

            $invalidEntries = $timeEntries->whereNotIn('account_id', $managedAccountIds);
            if ($invalidEntries->isNotEmpty()) {
                return response()->json(['error' => 'You cannot approve time entries for some of these accounts.'], 403);
            }
        }

        // Bulk approve
        $approvedCount = TimeEntry::whereIn('id', $validated['time_entry_ids'])
            ->where('status', 'pending')
            ->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_notes' => $validated['notes'] ?? null,
            ]);

        return response()->json([
            'message' => "Successfully approved {$approvedCount} time entries.",
            'approved_count' => $approvedCount,
        ]);
    }

    /**
     * Bulk reject time entries
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $user = $request->user();

        // Verify bulk rejection permissions - service providers, managers, and admins can bulk reject
        if (! ($user->user_type === 'service_provider' ||
              $user->hasAnyPermission(['time.manage', 'time.approve', 'teams.manage', 'admin.manage', 'admin.write']))) {
            return response()->json(['error' => 'Insufficient permissions for bulk rejection.'], 403);
        }

        $validated = $request->validate([
            'time_entry_ids' => 'required|array|min:1|max:100',
            'time_entry_ids.*' => 'exists:time_entries,id',
            'reason' => 'required|string|max:1000',
        ]);

        // Bulk reject
        $rejectedCount = TimeEntry::whereIn('id', $validated['time_entry_ids'])
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_notes' => $validated['reason'],
            ]);

        return response()->json([
            'message' => "Successfully rejected {$rejectedCount} time entries.",
            'rejected_count' => $rejectedCount,
        ]);
    }

    /**
     * Get approval statistics for managers/admins
     */
    public function approvalStats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Verify stats access
        if (! $user->hasAnyPermission(['teams.manage', 'admin.manage'])) {
            return response()->json(['error' => 'Insufficient permissions to view approval statistics.'], 403);
        }

        // Build query for accessible accounts
        $query = TimeEntry::query();

        if (! $user->hasPermission('admin.manage')) {
            $managedAccountIds = $user->accounts()
                ->whereHas('users', function ($userQuery) use ($user) {
                    $userQuery->where('users.id', $user->id);
                })
                ->pluck('accounts.id');
            $query->whereIn('account_id', $managedAccountIds);
        }

        $dateRange = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($dateRange);

        $stats = [
            'pending_count' => (clone $query)->where('status', 'pending')->count(),
            'approved_count' => (clone $query)->where('status', 'approved')
                ->where('approved_at', '>=', $startDate)->count(),
            'rejected_count' => (clone $query)->where('status', 'rejected')
                ->where('approved_at', '>=', $startDate)->count(),
            'total_hours_pending' => (clone $query)->where('status', 'pending')
                ->sum('duration') / 60, // Duration is stored in minutes
            'average_approval_time' => $this->getAverageApprovalTime($query, $startDate),
            'approval_rate' => $this->getApprovalRate($query, $startDate),
        ];

        return response()->json([
            'data' => $stats,
            'date_range_days' => $dateRange,
        ]);
    }

    /**
     * Calculate average approval time in hours
     */
    private function getAverageApprovalTime($query, $startDate): float
    {
        $approved = (clone $query)->where('status', 'approved')
            ->where('approved_at', '>=', $startDate)
            ->whereNotNull('approved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, approved_at)) as avg_hours')
            ->first();

        return $approved->avg_hours ?? 0;
    }

    /**
     * Get recent time entry statistics for dashboard widgets
     */
    public function recentStats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Build base query with user's accessible time entries
        $baseQuery = TimeEntry::query();

        // Apply user scope - simplified approach to avoid role template complexity
        $baseQuery->where('user_id', $user->id);

        // Get recent stats (last 30 days)
        $startDate = now()->subDays(30);

        $recentEntries = (clone $baseQuery)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->with(['account:id,name', 'user:id,name'])
            ->get();

        $stats = [
            'recent_entries' => TimeEntryResource::collection($recentEntries),
            'total_hours_month' => (clone $baseQuery)
                ->where('created_at', '>=', $startDate)
                ->sum('duration') ?? 0,
            'entries_count_month' => (clone $baseQuery)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'pending_approval_count' => (clone $baseQuery)
                ->where('status', 'pending')
                ->count(),
        ];

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Calculate approval rate percentage
     */
    private function getApprovalRate($query, $startDate): float
    {
        $total = (clone $query)->where('created_at', '>=', $startDate)
            ->whereIn('status', ['approved', 'rejected'])->count();
        $approved = (clone $query)->where('status', 'approved')
            ->where('approved_at', '>=', $startDate)->count();

        return $total > 0 ? ($approved / $total * 100) : 0;
    }

    /**
     * Get time entries for a specific ticket
     */
    public function forTicket(Request $request, \App\Models\Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        // Check if user can view this ticket
        if (! $ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        // Get time entries for this ticket
        $query = TimeEntry::with(['user:id,name,email', 'billingRate:id,rate'])
            ->where('ticket_id', $ticket->id)
            ->orderBy('started_at', 'desc');

        // Apply pagination
        $perPage = min($request->get('per_page', 15), 50);
        $timeEntries = $query->paginate($perPage);

        // Calculate totals
        $totalDuration = TimeEntry::where('ticket_id', $ticket->id)->sum('duration');
        $totalCost = TimeEntry::where('ticket_id', $ticket->id)
            ->whereHas('billingRate')
            ->get()
            ->sum(function ($entry) {
                return $entry->billingRate ? ($entry->duration / 60) * $entry->billingRate->rate : 0;
            });

        return response()->json([
            'data' => TimeEntryResource::collection($timeEntries->items()),
            'pagination' => [
                'current_page' => $timeEntries->currentPage(),
                'per_page' => $timeEntries->perPage(),
                'total' => $timeEntries->total(),
                'last_page' => $timeEntries->lastPage(),
                'has_more' => $timeEntries->hasMorePages(),
            ],
            'totals' => [
                'total_duration' => $totalDuration,
                'total_duration_formatted' => $this->formatDuration($totalDuration * 60), // Convert minutes to seconds for formatting
                'total_cost' => round($totalCost, 2),
                'entries_count' => $timeEntries->total(),
            ],
        ]);
    }

    /**
     * Format duration in seconds to human-readable format
     */
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else {
            return "{$minutes}m";
        }
    }
}
