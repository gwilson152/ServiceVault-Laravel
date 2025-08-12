<?php

namespace App\Services;

use App\Models\Timer;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class TimerService
{
    /**
     * Stop all running timers for a user (across all devices).
     */
    public function stopAllUserTimers(string $userId, ?string $deviceId = null): void
    {
        // Ignore device_id parameter - stop ALL user timers globally for simplicity
        $timers = Timer::where('user_id', $userId)
            ->where('status', 'running')
            ->get();

        foreach ($timers as $timer) {
            $timer->stop();
            $this->removeFromRedis($timer);
        }
    }

    /**
     * Stop a timer and optionally convert to time entry.
     */
    public function stopTimer(Timer $timer, array $options = []): array
    {
        $convertToEntry = $options['convert_to_entry'] ?? true;
        $roundTo = $options['round_to'] ?? 15; // Round to 15 minutes by default
        $notes = $options['notes'] ?? null;
        $manualDuration = $options['manual_duration'] ?? null;

        // Stop the timer
        $timer->stop();

        // Get timer duration in seconds
        $timerDurationSeconds = $timer->duration;
        
        // If manual duration provided (in minutes), use that for time entry
        if ($manualDuration) {
            $timeEntryDurationMinutes = $manualDuration;
        } else {
            // Convert timer seconds to minutes and apply rounding
            $timeEntryDurationMinutes = ceil($timerDurationSeconds / 60);
            if ($roundTo > 0) {
                $timeEntryDurationMinutes = ceil($timeEntryDurationMinutes / $roundTo) * $roundTo;
            }
        }

        $result = [
            'timer_duration' => $timerDurationSeconds, // Original timer duration in seconds
            'time_entry_duration' => $timeEntryDurationMinutes, // Time entry duration in minutes
            'duration_formatted' => $this->formatDuration($timerDurationSeconds),
            'time_entry' => null,
            'billed_amount' => null,
        ];

        // Convert to time entry if requested
        if ($convertToEntry) {
            $timeEntry = $timer->convertToTimeEntry([
                'duration' => $timeEntryDurationMinutes, // Store duration in minutes for time entry
                'notes' => $notes,
                'rounded' => $roundTo > 0 && !$manualDuration,
                'round_to' => $roundTo,
                'manual_override' => $manualDuration ? true : false,
                'original_timer_duration' => $timerDurationSeconds, // Track original timer duration
            ]);

            $result['time_entry'] = $timeEntry;
            
            // Calculate billed amount (convert time entry minutes to hours)
            if ($timer->billingRate) {
                $hours = $timeEntryDurationMinutes / 60;
                $result['billed_amount'] = round($hours * $timer->billingRate->rate, 2);
            }
        }

        // Remove from Redis
        $this->removeFromRedis($timer);

        return $result;
    }

    /**
     * Update timer state in Redis for cross-device sync (supports multiple timers).
     */
    public function updateRedisState(Timer $timer): void
    {
        $userKey = "timer:user:{$timer->user_id}:active";
        $timerKey = "timer:user:{$timer->user_id}:timer:{$timer->id}";
        
        $data = [
            'timer_id' => $timer->id,
            'status' => $timer->status,
            'started_at' => $timer->started_at->toIso8601String(),
            'paused_at' => $timer->paused_at?->toIso8601String(),
            'total_paused_duration' => $timer->total_paused_duration,
            'device_id' => $timer->device_id,
            'task_id' => $timer->task_id,
            'billing_rate_id' => $timer->billing_rate_id,
            'description' => $timer->description,
            'calculated_amount' => $timer->calculated_amount,
            'duration' => $timer->duration,
            'updated_at' => now()->toIso8601String(),
        ];

        // Store individual timer data
        Redis::setex($timerKey, 86400, json_encode($data));

        // Maintain list of active timer IDs for the user
        if ($timer->status === 'running' || $timer->status === 'paused') {
            Redis::sadd($userKey, $timer->id);
            Redis::expire($userKey, 86400);
        } else {
            Redis::srem($userKey, $timer->id);
        }

        // Update cache with all active timers for this user
        $this->refreshActiveTimersCache($timer->user_id);
    }

    /**
     * Get all active timer states from Redis for a user.
     */
    public function getRedisState(string $userId): array
    {
        // Try cache first
        $cached = Cache::get("user.{$userId}.active_timers");
        if ($cached) {
            return $cached;
        }

        // Get active timer IDs from Redis
        $userKey = "timer:user:{$userId}:active";
        $timerIds = Redis::smembers($userKey);
        
        $timers = [];
        // Handle case where Redis returns false (empty set)
        if ($timerIds && is_array($timerIds)) {
            foreach ($timerIds as $timerId) {
                $timerKey = "timer:user:{$userId}:timer:{$timerId}";
                $data = Redis::get($timerKey);
                if ($data) {
                    $timers[] = json_decode($data, true);
                }
            }
        }

        // Cache the result
        if (!empty($timers)) {
            Cache::put("user.{$userId}.active_timers", $timers, now()->addHours(24));
        }

        return $timers;
    }

    /**
     * Refresh the active timers cache for a user.
     */
    private function refreshActiveTimersCache(string $userId): void
    {
        // Clear existing cache
        Cache::forget("user.{$userId}.active_timers");
        Cache::forget("user.{$userId}.current_timer");
        
        // Rebuild cache with fresh data
        $this->getRedisState($userId);
    }

    /**
     * Remove timer from Redis.
     */
    public function removeFromRedis(Timer $timer): void
    {
        $userKey = "timer:user:{$timer->user_id}:active";
        $timerKey = "timer:user:{$timer->user_id}:timer:{$timer->id}";
        
        // Remove timer from active list
        Redis::srem($userKey, $timer->id);
        
        // Delete individual timer data
        Redis::del($timerKey);
        
        // Refresh cache
        $this->refreshActiveTimersCache($timer->user_id);
    }

    /**
     * Reconcile timer states across devices (supports multiple concurrent timers).
     */
    public function reconcileTimerConflicts(Collection $activeTimers, array $redisStates, ?string $deviceId): Collection
    {
        if (empty($redisStates)) {
            return $activeTimers;
        }

        $reconciledTimers = collect();
        $redisTimerIds = collect($redisStates)->pluck('timer_id');

        // Start with active timers from database
        foreach ($activeTimers as $timer) {
            // Check if this timer exists in Redis
            $redisTimer = collect($redisStates)->firstWhere('timer_id', $timer->id);
            
            if ($redisTimer) {
                // Compare timestamps to see which is more recent
                $redisUpdated = new \DateTime($redisTimer['updated_at']);
                $dbUpdated = $timer->updated_at;
                
                if ($redisUpdated > $dbUpdated) {
                    // Redis state is newer, sync from Redis
                    $timer->status = $redisTimer['status'];
                    if ($redisTimer['paused_at']) {
                        $timer->paused_at = new \DateTime($redisTimer['paused_at']);
                    }
                    $timer->total_paused_duration = $redisTimer['total_paused_duration'];
                    $timer->save();
                }
            }
            
            $reconciledTimers->push($timer);
        }

        // Add any Redis timers that aren't in the active collection
        foreach ($redisStates as $redisState) {
            if (!$activeTimers->contains('id', $redisState['timer_id'])) {
                $redisTimer = Timer::find($redisState['timer_id']);
                if ($redisTimer && ($redisTimer->status === 'running' || $redisTimer->status === 'paused')) {
                    $reconciledTimers->push($redisTimer);
                }
            }
        }

        // Update Redis with current state
        foreach ($reconciledTimers as $timer) {
            $this->updateRedisState($timer);
        }

        return $reconciledTimers->unique('id');
    }

    /**
     * Get user statistics for a date range.
     */
    public function getUserStatistics(string $userId, $startDate, $endDate): array
    {
        $timers = Timer::where('user_id', $userId)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->with('billingRate')
            ->get();

        $totalDuration = 0;
        $totalBilled = 0;
        // Note: Task breakdown removed - tasks no longer used in this system
        $dailyBreakdown = [];

        foreach ($timers as $timer) {
            $duration = $timer->duration;
            $totalDuration += $duration;
            
            // Calculate billed amount (duration is in seconds from Timer model)
            if ($timer->billingRate) {
                $hours = $duration / 3600; // Convert seconds to hours
                $amount = round($hours * $timer->billingRate->rate, 2);
                $totalBilled += $amount;
            }


            // Note: Task breakdown code removed - tasks no longer used

            // Daily breakdown
            $day = $timer->started_at->format('Y-m-d');
            if (!isset($dailyBreakdown[$day])) {
                $dailyBreakdown[$day] = [
                    'date' => $day,
                    'duration' => 0,
                    'amount' => 0,
                    'count' => 0,
                ];
            }
            $dailyBreakdown[$day]['duration'] += $duration;
            $dailyBreakdown[$day]['amount'] += $timer->calculated_amount ?? 0;
            $dailyBreakdown[$day]['count']++;
        }

        // Get all active timers
        $activeTimers = Timer::where('user_id', $userId)
            ->whereIn('status', ['running', 'paused'])
            ->get();

        $activeTimersData = [];
        $totalActiveAmount = 0;
        foreach ($activeTimers as $timer) {
            $activeTimersData[] = [
                'id' => $timer->id,
                'status' => $timer->status,
                'duration' => $timer->duration,
                'duration_formatted' => $timer->duration_formatted,
                'calculated_amount' => $timer->calculated_amount,
                'description' => $timer->description,
                // Note: task_name removed - tasks no longer used
                'started_at' => $timer->started_at->toIso8601String(),
            ];
            $totalActiveAmount += $timer->calculated_amount ?? 0;
        }

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'totals' => [
                'duration' => $totalDuration,
                'duration_formatted' => $this->formatDuration($totalDuration),
                'billed_amount' => $totalBilled,
                'timer_count' => $timers->count(),
                'average_duration' => $timers->count() > 0 ? round($totalDuration / $timers->count()) : 0,
            ],
            'active_timers' => [
                'count' => $activeTimers->count(),
                'total_running_amount' => $totalActiveAmount,
                'timers' => $activeTimersData,
            ],
            'breakdowns' => [
                // Note: by_task breakdown removed - tasks no longer used
                'by_day' => array_values($dailyBreakdown),
            ],
        ];
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
     * Get billing rates available for a user.
     */
    public function getAvailableBillingRates(User $user): Collection
    {
        // This would be expanded to include account-level rates, and user-specific overrides
        return DB::table('billing_rates')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('account_id', $user->account_id)
                    ->orWhereNull('user_id'); // System-wide rates
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Calculate estimated completion time for a budget.
     */
    public function calculateBudgetEstimates(Timer $timer, float $budgetAmount): array
    {
        if (!$timer->billingRate) {
            return [
                'hours_remaining' => null,
                'estimated_completion' => null,
            ];
        }

        $currentAmount = $timer->calculated_amount ?? 0;
        $remainingBudget = max(0, $budgetAmount - $currentAmount);
        $hoursRemaining = $remainingBudget / $timer->billingRate->rate;
        
        // Calculate estimated completion based on current pace
        $currentDurationSeconds = $timer->duration;
        if ($currentDurationSeconds > 0 && $currentAmount > 0) {
            $currentHours = $currentDurationSeconds / 3600; // Convert seconds to hours
            $pace = $currentAmount / $currentHours; // $ per hour
            $estimatedHours = $remainingBudget / $pace;
            $estimatedCompletion = now()->addHours($estimatedHours);
        } else {
            $estimatedCompletion = null;
        }

        return [
            'budget_total' => $budgetAmount,
            'budget_used' => $currentAmount,
            'budget_remaining' => $remainingBudget,
            'hours_remaining' => round($hoursRemaining, 2),
            'estimated_completion' => $estimatedCompletion?->toIso8601String(),
        ];
    }
}