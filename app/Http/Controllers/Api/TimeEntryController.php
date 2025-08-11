<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Models\Account;
use App\Models\Project;
use App\Http\Resources\TimeEntryResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class TimeEntryController extends Controller
{
    /**
     * Display a listing of time entries with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Build base query with user's accessible accounts
        $query = TimeEntry::with(['user:id,name', 'account:id,name', 'project:id,name']);
        
        // Apply user scope - employees see their own, managers/admins see team members
        if ($user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            // Admins see all time entries
        } elseif ($user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists()) {
            // Managers see entries for accounts they manage
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
            // Employees see only their own entries
            $query->where('user_id', $user->id);
        }
        
        // Apply filters
        $query->when($request->status, function ($q, $status) {
            $q->where('status', $status);
        })->when($request->user_id, function ($q, $userId) use ($user) {
            // Only managers/admins can filter by user
            if ($user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() ||
                $user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
                $q->where('user_id', $userId);
            }
        })->when($request->account_id, function ($q, $accountId) {
            $q->where('account_id', $accountId);
        })->when($request->project_id, function ($q, $projectId) {
            $q->where('project_id', $projectId);
        })->when($request->date_from, function ($q, $dateFrom) {
            $q->whereDate('date', '>=', $dateFrom);
        })->when($request->date_to, function ($q, $dateTo) {
            $q->whereDate('date', '<=', $dateTo);
        })->when($request->billable !== null, function ($q) use ($request) {
            $q->where('billable', $request->boolean('billable'));
        });
        
        // Apply sorting
        $sortField = $request->input('sort', 'date');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Get paginated results
        $timeEntries = $query->paginate($request->input('per_page', 20));
        
        // Add summary statistics
        $stats = [
            'total_hours' => $timeEntries->sum(function ($entry) {
                return $entry->duration / 3600;
            }),
            'billable_hours' => $timeEntries->where('billable', true)->sum(function ($entry) {
                return $entry->duration / 3600;
            }),
            'pending_count' => $timeEntries->where('status', 'pending')->count(),
            'approved_count' => $timeEntries->where('status', 'approved')->count(),
        ];
        
        return response()->json([
            'data' => TimeEntryResource::collection($timeEntries),
            'meta' => array_merge($timeEntries->toArray(), ['stats' => $stats])
        ]);
    }

    /**
     * Store a newly created time entry
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'description' => 'required|string|max:1000',
            'duration' => 'required|integer|min:60|max:86400', // 1 minute to 24 hours
            'date' => 'required|date|before_or_equal:today',
            'account_id' => 'required|exists:accounts,id',
            'project_id' => 'nullable|exists:projects,id',
            'billable' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'billing_rate_id' => 'nullable|exists:billing_rates,id'
        ]);
        
        // Verify user has access to the account
        if (!$user->accounts()->where('accounts.id', $validated['account_id'])->exists()) {
            return response()->json(['error' => 'You do not have access to this account.'], 403);
        }
        
        // Verify project belongs to account if specified
        if (!empty($validated['project_id'])) {
            $project = Project::find($validated['project_id']);
            if ($project->account_id !== $validated['account_id']) {
                return response()->json(['error' => 'Project does not belong to the specified account.'], 422);
            }
        }
        
        // Create time entry
        $timeEntry = TimeEntry::create([
            'user_id' => $user->id,
            'description' => $validated['description'],
            'duration' => $validated['duration'],
            'date' => $validated['date'],
            'account_id' => $validated['account_id'],
            'project_id' => $validated['project_id'] ?? null,
            'billable' => $validated['billable'] ?? true,
            'notes' => $validated['notes'] ?? null,
            'billing_rate_id' => $validated['billing_rate_id'] ?? null,
            'status' => 'pending', // Default status
        ]);
        
        $timeEntry->load(['user:id,name', 'account:id,name', 'project:id,name']);
        
        return response()->json([
            'data' => new TimeEntryResource($timeEntry),
            'message' => 'Time entry created successfully.'
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
            !$user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() &&
            !$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }
        
        $timeEntry->load(['user:id,name,email', 'account:id,name', 'project:id,name,description', 'billingRate']);
        
        return response()->json([
            'data' => new TimeEntryResource($timeEntry)
        ]);
    }

    /**
     * Update the specified time entry
     */
    public function update(Request $request, TimeEntry $timeEntry): JsonResponse
    {
        $user = $request->user();
        
        // Check permissions - users can edit their own pending entries, managers can edit team entries
        $canEdit = false;
        if ($timeEntry->user_id === $user->id && $timeEntry->status === 'pending') {
            $canEdit = true;
        } elseif ($user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() ||
                  $user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            $canEdit = true;
        }
        
        if (!$canEdit) {
            return response()->json(['error' => 'Cannot edit this time entry.'], 403);
        }
        
        $validated = $request->validate([
            'description' => 'sometimes|string|max:1000',
            'duration' => 'sometimes|integer|min:60|max:86400',
            'date' => 'sometimes|date|before_or_equal:today',
            'billable' => 'sometimes|boolean',
            'notes' => 'sometimes|nullable|string|max:2000',
            'billing_rate_id' => 'sometimes|nullable|exists:billing_rates,id'
        ]);
        
        // Reset status to pending if content changed (unless user is manager/admin)
        if (($request->has('description') || $request->has('duration') || $request->has('date')) &&
            $timeEntry->status === 'approved' &&
            $timeEntry->user_id === $user->id &&
            !$user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists()) {
            $validated['status'] = 'pending';
        }
        
        $timeEntry->update($validated);
        $timeEntry->load(['user:id,name', 'account:id,name', 'project:id,name']);
        
        return response()->json([
            'data' => new TimeEntryResource($timeEntry),
            'message' => 'Time entry updated successfully.'
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
        } elseif ($user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() ||
                  $user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            $canDelete = true;
        }
        
        if (!$canDelete) {
            return response()->json(['error' => 'Cannot delete this time entry.'], 403);
        }
        
        $timeEntry->delete();
        
        return response()->json([
            'message' => 'Time entry deleted successfully.'
        ]);
    }
    
    /**
     * Approve a time entry (managers/admins only)
     */
    public function approve(Request $request, TimeEntry $timeEntry): JsonResponse
    {
        $user = $request->user();
        
        // Verify approval permissions
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() &&
            !$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            return response()->json(['error' => 'Insufficient permissions to approve time entries.'], 403);
        }
        
        // Verify manager has access to this account
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            $managedAccountIds = $user->accounts()
                ->whereHas('users', function ($userQuery) use ($user) {
                    $userQuery->where('users.id', $user->id)
                             ->whereHas('roleTemplates', function ($roleQuery) {
                                 $roleQuery->whereJsonContains('permissions', 'teams.manage');
                             });
                })
                ->pluck('accounts.id');
                
            if (!$managedAccountIds->contains($timeEntry->account_id)) {
                return response()->json(['error' => 'You cannot approve time entries for this account.'], 403);
            }
        }
        
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000'
        ]);
        
        $timeEntry->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $validated['notes'] ?? null
        ]);
        
        $timeEntry->load(['user:id,name', 'account:id,name', 'project:id,name', 'approvedBy:id,name']);
        
        return response()->json([
            'data' => new TimeEntryResource($timeEntry),
            'message' => 'Time entry approved successfully.'
        ]);
    }
    
    /**
     * Reject a time entry (managers/admins only)
     */
    public function reject(Request $request, TimeEntry $timeEntry): JsonResponse
    {
        $user = $request->user();
        
        // Verify rejection permissions
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() &&
            !$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            return response()->json(['error' => 'Insufficient permissions to reject time entries.'], 403);
        }
        
        $validated = $request->validate([
            'reason' => 'required|string|max:1000'
        ]);
        
        $timeEntry->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'approval_notes' => $validated['reason']
        ]);
        
        $timeEntry->load(['user:id,name', 'account:id,name', 'project:id,name', 'approvedBy:id,name']);
        
        return response()->json([
            'data' => new TimeEntryResource($timeEntry),
            'message' => 'Time entry rejected successfully.'
        ]);
    }
    
    /**
     * Bulk approval of time entries
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Verify bulk approval permissions
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() &&
            !$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            return response()->json(['error' => 'Insufficient permissions for bulk approval.'], 403);
        }
        
        $validated = $request->validate([
            'time_entry_ids' => 'required|array|min:1|max:100',
            'time_entry_ids.*' => 'exists:time_entries,id',
            'notes' => 'nullable|string|max:1000'
        ]);
        
        // Get time entries and verify access
        $timeEntries = TimeEntry::whereIn('id', $validated['time_entry_ids'])
            ->where('status', 'pending')
            ->get();
            
        if ($timeEntries->isEmpty()) {
            return response()->json(['error' => 'No pending time entries found to approve.'], 422);
        }
        
        // Verify access to all accounts
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            $managedAccountIds = $user->accounts()
                ->whereHas('users', function ($userQuery) use ($user) {
                    $userQuery->where('users.id', $user->id)
                             ->whereHas('roleTemplates', function ($roleQuery) {
                                 $roleQuery->whereJsonContains('permissions', 'teams.manage');
                             });
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
                'approval_notes' => $validated['notes'] ?? null
            ]);
        
        return response()->json([
            'message' => "Successfully approved {$approvedCount} time entries.",
            'approved_count' => $approvedCount
        ]);
    }
    
    /**
     * Bulk reject time entries
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Verify bulk rejection permissions
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() &&
            !$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            return response()->json(['error' => 'Insufficient permissions for bulk rejection.'], 403);
        }
        
        $validated = $request->validate([
            'time_entry_ids' => 'required|array|min:1|max:100',
            'time_entry_ids.*' => 'exists:time_entries,id',
            'reason' => 'required|string|max:1000'
        ]);
        
        // Bulk reject
        $rejectedCount = TimeEntry::whereIn('id', $validated['time_entry_ids'])
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'approval_notes' => $validated['reason']
            ]);
        
        return response()->json([
            'message' => "Successfully rejected {$rejectedCount} time entries.",
            'rejected_count' => $rejectedCount
        ]);
    }
    
    /**
     * Get approval statistics for managers/admins
     */
    public function approvalStats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Verify stats access
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() &&
            !$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            return response()->json(['error' => 'Insufficient permissions to view approval statistics.'], 403);
        }
        
        // Build query for accessible accounts
        $query = TimeEntry::query();
        
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            $managedAccountIds = $user->accounts()
                ->whereHas('users', function ($userQuery) use ($user) {
                    $userQuery->where('users.id', $user->id)
                             ->whereHas('roleTemplates', function ($roleQuery) {
                                 $roleQuery->whereJsonContains('permissions', 'teams.manage');
                             });
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
                ->sum('duration') / 3600,
            'average_approval_time' => $this->getAverageApprovalTime($query, $startDate),
            'approval_rate' => $this->getApprovalRate($query, $startDate)
        ];
        
        return response()->json([
            'data' => $stats,
            'date_range_days' => $dateRange
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
            'data' => $stats
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
}
