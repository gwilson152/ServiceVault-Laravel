<?php

namespace App\Http\Controllers\Api;

use App\Events\TimerStarted;
use App\Events\TimerStopped;
use App\Events\TimerUpdated;
use App\Events\TimerDeleted;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTimerRequest;
use App\Http\Requests\UpdateTimerRequest;
use App\Http\Resources\TimerResource;
use App\Models\Timer;
use App\Services\TimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimerController extends Controller
{
    protected TimerService $timerService;

    public function __construct(TimerService $timerService)
    {
        $this->timerService = $timerService;
    }

    /**
     * Display a listing of the user's timers.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Timer::class);

        $query = Timer::with(['billingRate', 'ticket'])
            ->where('user_id', $request->user()->id);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Note: Project filtering removed - projects no longer used in this system

        // Filter by ticket
        if ($request->has('ticket_id')) {
            $query->where('ticket_id', $request->input('ticket_id'));
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('started_at', '>=', $request->input('start_date'));
        }
        if ($request->has('end_date')) {
            $query->whereDate('started_at', '<=', $request->input('end_date'));
        }

        // Include cross-device timers if requested
        if ($request->boolean('include_all_devices')) {
            $query->orWhere(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                    ->where('is_synced', true);
            });
        }

        $timers = $query->orderBy('started_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return TimerResource::collection($timers);
    }

    /**
     * Get active timers for the current user (for real-time sync)
     */
    public function active(Request $request): AnonymousResourceCollection
    {
        $timers = Timer::with(['billingRate', 'ticket'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'running')
            ->get();

        // Store in cache for cross-device sync
        Cache::put(
            "user.{$request->user()->id}.active_timers",
            $timers->pluck('id')->toArray(),
            now()->addMinutes(5)
        );

        return TimerResource::collection($timers);
    }

    /**
     * Store a newly created timer.
     */
    public function store(StoreTimerRequest $request): JsonResponse
    {
        // Validate that user is an Agent (can create timers for time entry conversion)
        $user = $request->user();
        if (!$user->canCreateTimeEntries()) {
            return response()->json([
                'message' => 'Only Agents can create timers for time tracking purposes.',
                'error' => 'User type validation failed'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Use provided user_id if valid and user has permission, otherwise use current user
            $userId = $request->input('user_id') && $user->hasPermission('timers.manage') 
                ? $request->input('user_id') 
                : $user->id;
            $ticketId = $request->input('ticket_id');

            // If ticket_id is provided, enforce per-ticket timer limitation
            if ($ticketId) {
                // Check if user already has an active timer for this ticket
                if (Timer::userHasActiveTimerForTicket($userId, $ticketId)) {
                    return response()->json([
                        'message' => 'You already have an active timer for this ticket. Please stop it first.',
                        'existing_timer' => new TimerResource(Timer::getUserActiveTimerForTicket($userId, $ticketId))
                    ], 422);
                }
            }

            // Stop any running timers if single timer mode is requested (now defaults to false)
            if ($request->boolean('stop_others', false)) {
                $this->timerService->stopAllUserTimers($userId, $request->input('device_id'));
            }

            $timer = Timer::create([
                'user_id' => $userId,
                'billing_rate_id' => $request->input('billing_rate_id'),
                'ticket_id' => $ticketId,
                'account_id' => $request->input('account_id'),
                'description' => $request->input('description'),
                'status' => 'running',
                'started_at' => now(),
                'device_id' => $request->input('device_id'),
                'is_synced' => true,
                'metadata' => [
                    'client_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'started_via' => 'api',
                ],
            ]);

            // Broadcast timer started event for real-time sync
            broadcast(new TimerStarted($timer))->toOthers();

            // Update Redis state for cross-device sync
            $this->timerService->updateRedisState($timer);

            DB::commit();

            return response()->json([
                'message' => 'Timer started successfully',
                'data' => new TimerResource($timer->load(['billingRate'])),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to start timer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified timer.
     */
    public function show(Timer $timer): TimerResource
    {
        return new TimerResource($timer->load(['billingRate', 'timeEntry']));
    }

    /**
     * Update the specified timer (including while running).
     */
    public function update(UpdateTimerRequest $request, Timer $timer): JsonResponse
    {
        DB::beginTransaction();
        try {
            $originalStatus = $timer->status;
            $originalBillingRateId = $timer->billing_rate_id;

            // Allow updating description, billing rate, and other fields while timer is running
            $timer->fill($request->validated());

            // Handle status changes
            if ($request->has('status') && $request->input('status') !== $originalStatus) {
                if ($request->input('status') === 'paused') {
                    $timer->paused_at = now();
                    $timer->total_paused_duration = $timer->total_paused_duration ?? 0;
                } elseif ($request->input('status') === 'running' && $originalStatus === 'paused') {
                    $pausedDuration = $timer->paused_at ? max(0, (int) now()->diffInSeconds($timer->paused_at)) : 0;
                    $timer->total_paused_duration = max(0, (int) (($timer->total_paused_duration ?? 0) + $pausedDuration));
                    $timer->paused_at = null;
                }
            }

            // Track billing rate changes in metadata
            if ($request->has('billing_rate_id') && $request->input('billing_rate_id') !== $originalBillingRateId) {
                $timer->metadata = array_merge($timer->metadata ?? [], [
                    'billing_rate_changed' => true,
                    'billing_rate_changed_at' => now()->toIso8601String(),
                    'previous_billing_rate_id' => $originalBillingRateId,
                ]);
            }

            $timer->save();

            // Load billing rate for calculated amount
            $timer->load('billingRate');

            // Broadcast update for real-time sync (includes calculated amount)
            broadcast(new TimerUpdated($timer))->toOthers();

            // Update Redis state
            $this->timerService->updateRedisState($timer);

            DB::commit();

            return response()->json([
                'message' => 'Timer updated successfully',
                'data' => new TimerResource($timer->load(['billingRate'])),
                'running_amount' => $timer->calculated_amount,
                'hourly_rate' => $timer->billingRate ? $timer->billingRate->rate : null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update timer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stop a running timer and optionally convert to time entry
     */
    public function stop(Request $request, Timer $timer): JsonResponse
    {
        $this->authorize('update', $timer);

        DB::beginTransaction();
        try {
            $result = $this->timerService->stopTimer($timer, [
                'convert_to_entry' => $request->boolean('convert_to_entry', true),
                'round_to' => $request->input('round_to', 15), // Round to 15 minutes by default
                'notes' => $request->input('notes'),
            ]);

            // Broadcast timer stopped event
            broadcast(new TimerStopped($timer))->toOthers();

            DB::commit();

            return response()->json([
                'message' => 'Timer stopped successfully',
                'data' => [
                    'timer' => new TimerResource($timer->fresh()),
                    'time_entry' => $result['time_entry'] ?? null,
                    'duration' => $result['duration'],
                    'billed_amount' => $result['billed_amount'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to stop timer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pause a running timer
     */
    public function pause(Timer $timer): JsonResponse
    {
        $this->authorize('update', $timer);

        if ($timer->status !== 'running') {
            return response()->json([
                'message' => 'Timer is not running',
            ], 400);
        }

        $timer->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);

        // Broadcast update
        broadcast(new TimerUpdated($timer))->toOthers();

        // Update Redis state
        $this->timerService->updateRedisState($timer);

        return response()->json([
            'message' => 'Timer paused successfully',
            'data' => new TimerResource($timer),
        ]);
    }

    /**
     * Resume a paused timer
     */
    public function resume(Timer $timer): JsonResponse
    {
        try {
            $this->authorize('update', $timer);

            if ($timer->status !== 'paused') {
                return response()->json([
                    'message' => 'Timer is not paused',
                ], 400);
            }

            // Calculate paused duration more safely
            $pausedDuration = 0;
            if ($timer->paused_at) {
                $pausedDuration = max(0, (int) now()->diffInSeconds($timer->paused_at));
            }

            // Ensure total_paused_duration is always a non-negative integer
            $totalPausedDuration = max(0, (int) (($timer->total_paused_duration ?? 0) + $pausedDuration));

            $timer->update([
                'status' => 'running',
                'paused_at' => null,
                'total_paused_duration' => $totalPausedDuration,
            ]);

            // Broadcast update
            try {
                broadcast(new TimerUpdated($timer))->toOthers();
            } catch (\Exception $e) {
                // Log broadcast error but don't fail the request
                \Log::warning('Failed to broadcast timer update: ' . $e->getMessage());
            }

            // Update Redis state
            try {
                $this->timerService->updateRedisState($timer);
            } catch (\Exception $e) {
                // Log Redis error but don't fail the request
                \Log::warning('Failed to update Redis state: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Timer resumed successfully',
                'data' => new TimerResource($timer),
            ]);
        } catch (\Exception $e) {
            \Log::error('Timer resume error: ' . $e->getMessage(), [
                'timer_id' => $timer->id ?? 'unknown',
                'user_id' => auth()->id() ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to resume timer',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Commit a timer - stop it and convert to time entry immediately
     */
    public function commit(Request $request, Timer $timer): JsonResponse
    {
        $this->authorize('update', $timer);

        DB::beginTransaction();
        try {
            // Update description if provided
            if ($request->has('description')) {
                $timer->description = $request->input('description');
                $timer->save();
            }

            // Stop and convert to time entry
            $result = $this->timerService->stopTimer($timer, [
                'convert_to_entry' => true,
                'round_to' => $request->input('round_to', 15),
                'notes' => $request->input('notes'),
                'manual_duration' => $request->input('duration'), // Allow manual duration override (in minutes)
            ]);

            // Broadcast timer stopped event
            broadcast(new TimerStopped($timer))->toOthers();

            DB::commit();

            return response()->json([
                'message' => 'Timer committed successfully',
                'data' => [
                    'timer' => new TimerResource($timer->fresh()),
                    'time_entry' => $result['time_entry'],
                    'duration' => $result['duration'],
                    'billed_amount' => $result['billed_amount'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to commit timer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark timer as committed when time entry already exists
     * This is used when the time entry is created separately (e.g., via UnifiedTimeEntryDialog)
     */
    public function markCommitted(Request $request, Timer $timer): JsonResponse
    {
        $this->authorize('update', $timer);

        $validated = $request->validate([
            'time_entry_id' => 'required|exists:time_entries,id'
        ]);

        try {
            // Mark timer as committed using the model method
            $timer->commit();

            // Remove timer from Redis cache
            $this->timerService->removeFromRedis($timer);

            // Broadcast timer committed event
            broadcast(new TimerStopped($timer))->toOthers();

            return response()->json([
                'message' => 'Timer marked as committed successfully',
                'data' => new TimerResource($timer->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to mark timer as committed: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Cancel a timer (mark as canceled without deleting)
     */
    public function cancel(Timer $timer): JsonResponse
    {
        $this->authorize('update', $timer);

        try {
            // Only allow canceling active timers
            if (!in_array($timer->status, ['running', 'paused'])) {
                return response()->json([
                    'error' => 'Timer cannot be canceled. Current status: ' . $timer->status
                ], 422);
            }

            // Cancel the timer using the model method
            $timer->cancel();
            
            // Remove timer from Redis cache
            $this->timerService->removeFromRedis($timer);
            
            // Broadcast timer canceled event (reuse TimerStopped for now)
            broadcast(new TimerStopped($timer))->toOthers();
            
            return response()->json([
                'message' => 'Timer canceled successfully',
                'data' => new TimerResource($timer->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to cancel timer: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Adjust timer duration manually
     */
    public function adjustDuration(Request $request, Timer $timer): JsonResponse
    {
        $this->authorize('update', $timer);

        $request->validate([
            'duration' => 'required|integer|min:0', // Duration in seconds
            'adjustment_type' => 'in:set,add,subtract', // How to apply the adjustment
        ]);

        DB::beginTransaction();
        try {
            $adjustmentType = $request->input('adjustment_type', 'set');
            $duration = $request->input('duration');

            switch ($adjustmentType) {
                case 'add':
                    // Add time to the timer by adjusting started_at
                    $timer->started_at = $timer->started_at->subSeconds($duration);
                    break;
                case 'subtract':
                    // Subtract time from the timer
                    $timer->started_at = $timer->started_at->addSeconds($duration);
                    break;
                case 'set':
                default:
                    // Set exact duration by calculating new started_at
                    $currentDuration = $timer->duration;
                    $difference = $duration - $currentDuration;
                    $timer->started_at = $timer->started_at->subSeconds($difference);
                    break;
            }

            $timer->metadata = array_merge($timer->metadata ?? [], [
                'manually_adjusted' => true,
                'adjusted_at' => now()->toIso8601String(),
                'adjusted_by' => $request->user()->id,
            ]);

            $timer->save();

            // Broadcast update
            broadcast(new TimerUpdated($timer))->toOthers();

            // Update Redis state
            $this->timerService->updateRedisState($timer);

            DB::commit();

            return response()->json([
                'message' => 'Timer duration adjusted successfully',
                'data' => new TimerResource($timer->load(['billingRate'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to adjust timer duration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync timer state across devices
     */
    public function sync(Request $request): JsonResponse
    {
        $this->authorize('sync', Timer::class);
        $deviceId = $request->input('device_id');
        $userId = $request->user()->id;

        // Get current states from Redis (now returns array of all active timers)
        $currentStates = $this->timerService->getRedisState($userId);

        // Get active timers from database
        $activeTimers = Timer::where('user_id', $userId)
            ->whereIn('status', ['running', 'paused'])
            ->get();

        // Reconcile any conflicts
        $reconciledTimers = $this->timerService->reconcileTimerConflicts(
            $activeTimers,
            $currentStates,
            $deviceId
        );

        return response()->json([
            'message' => 'Timer state synchronized',
            'data' => [
                'timers' => TimerResource::collection($reconciledTimers),
                'device_id' => $deviceId,
                'synced_at' => now(),
            ],
        ]);
    }

    /**
     * Get all current active timers with running calculations
     */
    public function current(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Timer::class);

        $timers = Timer::with(['billingRate'])
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['running', 'paused'])
            ->orderBy('started_at', 'desc')
            ->get();

        if ($timers->isEmpty()) {
            return response()->json([
                'message' => 'No active timers',
                'data' => [],
                'totals' => [
                    'count' => 0,
                    'total_running_amount' => 0,
                    'total_duration' => 0,
                ],
            ]);
        }

        $totalAmount = 0;
        $totalDuration = 0;
        $timerData = [];

        foreach ($timers as $timer) {
            $currentDuration = $timer->duration;
            $currentAmount = $timer->calculated_amount;
            $totalAmount += $currentAmount ?? 0;
            $totalDuration += $currentDuration;

            $timerData[] = [
                'timer' => new TimerResource($timer),
                'calculations' => [
                    'duration_seconds' => $currentDuration,
                    'duration_formatted' => $timer->duration_formatted,
                    'running_amount' => $currentAmount,
                    'hourly_rate' => $timer->billingRate ? $timer->billingRate->rate : null,
                    'estimated_hourly_earnings' => $currentAmount ? ($currentAmount / ($currentDuration / 3600)) : 0,
                ],
            ];
        }

        return response()->json([
            'data' => $timerData,
            'totals' => [
                'count' => $timers->count(),
                'total_running_amount' => round($totalAmount, 2),
                'total_duration' => $totalDuration,
                'total_duration_formatted' => $this->formatDuration($totalDuration),
            ],
        ]);
    }

    /**
     * Format duration in seconds to human-readable format.
     */
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $secs);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $secs);
        } else {
            return sprintf('%ds', $secs);
        }
    }

    /**
     * Get timer statistics for the user
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $request->user();
        $includeTickets = $request->boolean('include_tickets', false);
        $status = $request->input('status');

        // Get base statistics from TimerService
        $stats = $this->timerService->getUserStatistics(
            $user->id,
            $request->input('start_date', now()->startOfMonth()),
            $request->input('end_date', now()->endOfMonth())
        );

        // If requesting active timers with tickets (for widget)
        if ($includeTickets && $status === 'active') {
            $activeTimers = Timer::with(['ticket:id,ticket_number,title', 'billingRate'])
                ->where('user_id', $user->id)
                ->whereIn('status', ['running', 'paused'])
                ->get();

            $totalElapsed = 0;
            $estimatedValue = 0;

            foreach ($activeTimers as $timer) {
                $elapsed = $timer->getElapsedSeconds();
                $totalElapsed += $elapsed;

                if ($timer->billingRate) {
                    $hours = $elapsed / 3600;
                    $estimatedValue += $hours * $timer->billingRate->rate;
                }
            }

            $stats['active_timers'] = $activeTimers->map(function ($timer) {
                return [
                    'id' => $timer->id,
                    'description' => $timer->description,
                    'status' => $timer->status,
                    'start_time' => $timer->started_at,
                    'elapsed_seconds' => $timer->getElapsedSeconds(),
                    'ticket' => $timer->ticket ? [
                        'id' => $timer->ticket->id,
                        'ticket_number' => $timer->ticket->ticket_number,
                        'title' => $timer->ticket->title,
                    ] : null,
                ];
            });

            $stats['total_elapsed'] = $totalElapsed;
            $stats['estimated_value'] = round($estimatedValue, 2);
        }

        return response()->json($stats);
    }

    /**
     * Remove the specified timer from storage (can be called from timer overlay).
     */
    public function destroy(Request $request, Timer $timer): JsonResponse
    {
        // Force delete option for timer overlay
        $forceDelete = $request->boolean('force', false);

        // Only allow deletion of non-running timers unless force delete
        if (!$forceDelete && $timer->status === 'running') {
            return response()->json([
                'message' => 'Cannot delete a running timer. Please stop it first or use force delete.',
            ], 400);
        }

        // Check if timer has been converted to time entry
        if ($timer->time_entry_id && !$forceDelete) {
            return response()->json([
                'message' => 'Cannot delete a timer that has been converted to a time entry.',
            ], 400);
        }

        // If force deleting a running timer, stop it first
        if ($forceDelete && $timer->status === 'running') {
            $timer->stop();
        }

        // Broadcast deletion event before deleting
        broadcast(new TimerDeleted($timer))->toOthers();

        $timer->delete();

        // Clear from Redis if exists
        $this->timerService->removeFromRedis($timer);

        return response()->json([
            'message' => 'Timer deleted successfully',
            'was_forced' => $forceDelete,
        ]);
    }

    /**
     * Get all timers for a specific ticket
     */
    public function forTicket(Request $request, string $ticketId): AnonymousResourceCollection
    {
        $user = $request->user();

        // Check if user has timer permissions
        if (!$user->hasAnyPermission(['timers.read', 'timers.write', 'time.track'])) {
            abort(403, 'Insufficient permissions to view timers.');
        }

        $request->validate([
            'include_all_statuses' => 'boolean',
        ]);

        $query = Timer::with(['user', 'billingRate', 'ticket'])
            ->where('ticket_id', $ticketId);

        // By default, only show active timers
        if (!$request->boolean('include_all_statuses', false)) {
            $query->whereIn('status', ['running', 'paused']);
        }

        // Filter by status if specified
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $timers = $query->orderBy('started_at', 'desc')->get();

        return TimerResource::collection($timers);
    }

    /**
     * Start a timer for a specific ticket
     */
    public function startForTicket(Request $request, string $ticketId): JsonResponse
    {
        // Validate that user is an Agent (can create timers for time entry conversion)
        $user = $request->user();
        if (!$user->canCreateTimeEntries()) {
            return response()->json([
                'message' => 'Only Agents can create timers for time tracking purposes.',
                'error' => 'User type validation failed'
            ], 403);
        }

        $request->validate([
            'description' => 'required|string|max:1000',
            'billing_rate_id' => 'nullable|exists:billing_rates,id',
        ]);

        $userId = $user->id;

        // Check if user already has an active timer for this ticket
        if (Timer::userHasActiveTimerForTicket($userId, $ticketId)) {
            return response()->json([
                'message' => 'You already have an active timer for this ticket. Please stop it first.',
                'existing_timer' => new TimerResource(Timer::getUserActiveTimerForTicket($userId, $ticketId))
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Get ticket info for account_id
            $ticket = \App\Models\Ticket::findOrFail($ticketId);

            $timer = Timer::create([
                'user_id' => $userId,
                'ticket_id' => $ticketId,
                'account_id' => $ticket->account_id,
                'billing_rate_id' => $request->input('billing_rate_id'),
                'description' => $request->input('description'),
                'status' => 'running',
                'started_at' => now(),
                'device_id' => $request->input('device_id', 'web-' . uniqid()),
                'is_synced' => true,
                'metadata' => [
                    'client_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'started_via' => 'ticket_api',
                ],
            ]);

            // Broadcast timer started event
            broadcast(new TimerStarted($timer))->toOthers();

            // Update Redis state
            $this->timerService->updateRedisState($timer);

            DB::commit();

            return response()->json([
                'message' => 'Timer started for ticket',
                'data' => new TimerResource($timer->load(['ticket', 'billingRate'])),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to start timer for ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active timers for a ticket (real-time dashboard)
     */
    public function activeForTicket(Request $request, string $ticketId): JsonResponse
    {
        $user = $request->user();

        // Check if user has timer permissions
        if (!$user->hasAnyPermission(['timers.read', 'timers.write', 'time.track'])) {
            return response()->json(['error' => 'Insufficient permissions to view timers.'], 403);
        }

        $timers = Timer::with(['user', 'billingRate', 'ticket'])
            ->where('ticket_id', $ticketId)
            ->whereIn('status', ['running', 'paused'])
            ->get();

        // Return the timers directly as the frontend expects them
        return response()->json([
            'data' => TimerResource::collection($timers),
        ]);
    }

    /**
     * Bulk operations on timers
     */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate([
            'timer_ids' => 'required|array',
            'timer_ids.*' => 'exists:timers,id',
            'action' => 'required|in:stop,delete,pause,resume',
        ]);

        $timers = Timer::whereIn('id', $request->input('timer_ids'))
            ->where('user_id', $request->user()->id)
            ->get();

        $results = [];

        foreach ($timers as $timer) {
            try {
                switch ($request->input('action')) {
                    case 'stop':
                        $this->timerService->stopTimer($timer);
                        $results[] = ['id' => $timer->id, 'status' => 'stopped'];
                        break;
                    case 'delete':
                        if ($timer->status !== 'running' && !$timer->time_entry_id) {
                            $timer->delete();
                            $results[] = ['id' => $timer->id, 'status' => 'deleted'];
                        } else {
                            $results[] = ['id' => $timer->id, 'status' => 'skipped', 'reason' => 'Timer is running or converted'];
                        }
                        break;
                    case 'pause':
                        if ($timer->status === 'running') {
                            $timer->update(['status' => 'paused', 'paused_at' => now()]);
                            $results[] = ['id' => $timer->id, 'status' => 'paused'];
                        }
                        break;
                    case 'resume':
                        if ($timer->status === 'paused') {
                            $pausedDuration = $timer->paused_at ? max(0, (int) now()->diffInSeconds($timer->paused_at)) : 0;
                            $totalPausedDuration = max(0, (int) (($timer->total_paused_duration ?? 0) + $pausedDuration));
                            $timer->update([
                                'status' => 'running',
                                'paused_at' => null,
                                'total_paused_duration' => $totalPausedDuration,
                            ]);
                            $results[] = ['id' => $timer->id, 'status' => 'resumed'];
                        }
                        break;
                }

                // Broadcast updates for each timer
                broadcast(new TimerUpdated($timer))->toOthers();
            } catch (\Exception $e) {
                $results[] = ['id' => $timer->id, 'status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return response()->json([
            'message' => 'Bulk operation completed',
            'results' => $results,
        ]);
    }

    /**
     * Get all active timers across all users (Admin only)
     */
    public function allActive(Request $request): JsonResponse
    {
        // Check admin permissions
        $user = $request->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('admin.read')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $timers = Timer::with(['user', 'billingRate', 'ticket'])
            ->whereIn('status', ['running', 'paused'])
            ->orderBy('started_at', 'desc')
            ->get();

        $totalDuration = 0;
        $totalAmount = 0;
        $timerData = [];

        foreach ($timers as $timer) {
            $currentDuration = $timer->duration;
            $currentAmount = $timer->calculated_amount;

            $totalAmount += $currentAmount ?? 0;
            $totalDuration += $currentDuration;

            $timerData[] = [
                'timer' => new TimerResource($timer),
                'user' => [
                    'id' => $timer->user->id,
                    'name' => $timer->user->name,
                    'email' => $timer->user->email,
                ],
                'calculations' => [
                    'duration_seconds' => $currentDuration,
                    'duration_formatted' => $timer->duration_formatted,
                    'running_amount' => $currentAmount,
                    'hourly_rate' => $timer->billingRate ? $timer->billingRate->rate : null,
                ],
            ];
        }

        return response()->json([
            'data' => $timerData,
            'totals' => [
                'active_timers' => $timers->count(),
                'total_amount' => round($totalAmount, 2),
                'total_duration' => $totalDuration,
                'total_duration_formatted' => $this->formatDuration($totalDuration),
            ],
        ]);
    }

    /**
     * Admin action: Pause any user's timer
     */
    public function adminPauseTimer(Request $request, Timer $timer): JsonResponse
    {
        // Check admin permissions
        $user = $request->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('admin.write')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($timer->status !== 'running') {
            return response()->json(['message' => 'Timer is not running'], 400);
        }

        $timer->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);

        // Broadcast update to the timer owner
        broadcast(new TimerUpdated($timer))->toOthers();

        // Update Redis state
        $this->timerService->updateRedisState($timer);

        return response()->json([
            'message' => 'Timer paused successfully',
            'data' => new TimerResource($timer),
        ]);
    }

    /**
     * Admin action: Resume any user's timer
     */
    public function adminResumeTimer(Request $request, Timer $timer): JsonResponse
    {
        // Check admin permissions
        $user = $request->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('admin.write')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($timer->status !== 'paused') {
            return response()->json(['message' => 'Timer is not paused'], 400);
        }

        $pausedDuration = $timer->paused_at ? max(0, (int) now()->diffInSeconds($timer->paused_at)) : 0;
        $totalPausedDuration = max(0, (int) (($timer->total_paused_duration ?? 0) + $pausedDuration));

        $timer->update([
            'status' => 'running',
            'paused_at' => null,
            'total_paused_duration' => $totalPausedDuration,
        ]);

        // Broadcast update to the timer owner
        broadcast(new TimerUpdated($timer))->toOthers();

        // Update Redis state
        $this->timerService->updateRedisState($timer);

        return response()->json([
            'message' => 'Timer resumed successfully',
            'data' => new TimerResource($timer),
        ]);
    }

    /**
     * Admin action: Stop any user's timer
     */
    public function adminStopTimer(Request $request, Timer $timer): JsonResponse
    {
        // Check admin permissions
        $user = $request->user();
        if (!$user->isSuperAdmin() && !$user->hasPermission('admin.write')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            $result = $this->timerService->stopTimer($timer, [
                'convert_to_entry' => true,
                'notes' => 'Stopped by administrator: ' . $user->name,
            ]);

            // Broadcast timer stopped event to the timer owner
            broadcast(new TimerStopped($timer))->toOthers();

            DB::commit();

            return response()->json([
                'message' => 'Timer stopped successfully',
                'data' => [
                    'timer' => new TimerResource($timer->fresh()),
                    'time_entry' => $result['time_entry'] ?? null,
                    'duration' => $result['duration'],
                    'billed_amount' => $result['billed_amount'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to stop timer',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active timers for multiple tickets in bulk
     */
    public function bulkActiveForTickets(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Timer::class);

        $request->validate([
            'ticket_ids' => 'required|array|min:1|max:100',
            'ticket_ids.*' => 'string|exists:tickets,id',
        ]);

        $ticketIds = $request->input('ticket_ids');

        // Get all active timers for the provided ticket IDs
        $timers = Timer::with(['user', 'billingRate', 'ticket'])
            ->whereIn('ticket_id', $ticketIds)
            ->whereIn('status', ['running', 'paused'])
            ->get();

        // Group timers by ticket_id for easier frontend consumption
        $timersByTicket = [];
        foreach ($ticketIds as $ticketId) {
            $timersByTicket[$ticketId] = [];
        }

        foreach ($timers as $timer) {
            $timersByTicket[$timer->ticket_id][] = new TimerResource($timer);
        }

        return response()->json([
            'message' => 'Active timers retrieved successfully',
            'data' => $timersByTicket,
            'meta' => [
                'tickets_requested' => count($ticketIds),
                'tickets_with_timers' => count(array_filter($timersByTicket, fn($timers) => !empty($timers))),
                'total_active_timers' => $timers->count(),
            ]
        ]);
    }
}
