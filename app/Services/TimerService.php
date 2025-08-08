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
     * Stop all running timers for a user on a specific device.
     */
    public function stopAllUserTimers(int $userId, ?string $deviceId = null): void
    {
        $query = Timer::where('user_id', $userId)
            ->where('status', 'running');

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $timers = $query->get();

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

        // Calculate duration
        $duration = $manualDuration ?? $timer->duration;
        
        // Round duration if specified
        if ($roundTo > 0 && !$manualDuration) {
            $minutes = ceil($duration / 60);
            $roundedMinutes = ceil($minutes / $roundTo) * $roundTo;
            $duration = $roundedMinutes * 60;
        }

        $result = [
            'duration' => $duration,
            'duration_formatted' => $this->formatDuration($duration),
            'time_entry' => null,
            'billed_amount' => null,
        ];

        // Convert to time entry if requested
        if ($convertToEntry) {
            $timeEntry = $timer->convertToTimeEntry([
                'duration' => $duration,
                'notes' => $notes,
                'rounded' => $roundTo > 0,
                'round_to' => $roundTo,
            ]);

            $result['time_entry'] = $timeEntry;
            
            // Calculate billed amount
            if ($timer->billingRate) {
                $hours = $duration / 3600;
                $result['billed_amount'] = round($hours * $timer->billingRate->rate, 2);
            }
        }

        // Remove from Redis
        $this->removeFromRedis($timer);

        return $result;
    }

    /**
     * Update timer state in Redis for cross-device sync.
     */
    public function updateRedisState(Timer $timer): void
    {
        $key = "timer:user:{$timer->user_id}:active";
        $data = [
            'timer_id' => $timer->id,
            'status' => $timer->status,
            'started_at' => $timer->started_at->toIso8601String(),
            'paused_at' => $timer->paused_at?->toIso8601String(),
            'total_paused_duration' => $timer->total_paused_duration,
            'device_id' => $timer->device_id,
            'project_id' => $timer->project_id,
            'task_id' => $timer->task_id,
            'billing_rate_id' => $timer->billing_rate_id,
            'description' => $timer->description,
            'calculated_amount' => $timer->calculated_amount,
            'duration' => $timer->duration,
            'updated_at' => now()->toIso8601String(),
        ];

        // Store in Redis with 24-hour expiry
        Redis::setex($key, 86400, json_encode($data));

        // Also update in cache for faster access
        Cache::put("user.{$timer->user_id}.current_timer", $data, now()->addHours(24));
    }

    /**
     * Get timer state from Redis.
     */
    public function getRedisState(int $userId): ?array
    {
        // Try cache first
        $cached = Cache::get("user.{$userId}.current_timer");
        if ($cached) {
            return $cached;
        }

        // Fall back to Redis
        $key = "timer:user:{$userId}:active";
        $data = Redis::get($key);
        
        if ($data) {
            $decoded = json_decode($data, true);
            Cache::put("user.{$userId}.current_timer", $decoded, now()->addHours(24));
            return $decoded;
        }

        return null;
    }

    /**
     * Remove timer from Redis.
     */
    public function removeFromRedis(Timer $timer): void
    {
        $key = "timer:user:{$timer->user_id}:active";
        Redis::del($key);
        Cache::forget("user.{$timer->user_id}.current_timer");
        Cache::forget("user.{$timer->user_id}.active_timers");
    }

    /**
     * Reconcile timer conflicts across devices.
     */
    public function reconcileTimerConflicts(Collection $activeTimers, ?array $redisState, ?string $deviceId): Collection
    {
        if (!$redisState) {
            return $activeTimers;
        }

        $redisTimerId = $redisState['timer_id'] ?? null;
        $redisDeviceId = $redisState['device_id'] ?? null;

        // If Redis has a different timer than what's in the database
        if ($redisTimerId) {
            $redisTimer = Timer::find($redisTimerId);
            
            if ($redisTimer && !$activeTimers->contains('id', $redisTimerId)) {
                // Add the Redis timer to the collection if it's more recent
                if ($redisTimer->updated_at > $activeTimers->max('updated_at')) {
                    // Stop older timers
                    foreach ($activeTimers as $timer) {
                        if ($timer->id !== $redisTimerId) {
                            $timer->stop();
                        }
                    }
                    
                    return collect([$redisTimer]);
                }
            }
        }

        // If same device, prefer local state
        if ($deviceId && $redisDeviceId === $deviceId) {
            return $activeTimers;
        }

        // Otherwise, prefer the most recently updated timer
        $mostRecent = $activeTimers->sortByDesc('updated_at')->first();
        
        if ($mostRecent) {
            // Stop all other timers
            foreach ($activeTimers as $timer) {
                if ($timer->id !== $mostRecent->id) {
                    $timer->stop();
                }
            }
            
            return collect([$mostRecent]);
        }

        return $activeTimers;
    }

    /**
     * Get user statistics for a date range.
     */
    public function getUserStatistics(int $userId, $startDate, $endDate): array
    {
        $timers = Timer::where('user_id', $userId)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->with('billingRate', 'project', 'task')
            ->get();

        $totalDuration = 0;
        $totalBilled = 0;
        $projectBreakdown = [];
        $taskBreakdown = [];
        $dailyBreakdown = [];

        foreach ($timers as $timer) {
            $duration = $timer->duration;
            $totalDuration += $duration;
            
            // Calculate billed amount
            if ($timer->billingRate) {
                $hours = $duration / 3600;
                $amount = round($hours * $timer->billingRate->rate, 2);
                $totalBilled += $amount;
            }

            // Project breakdown
            $projectKey = $timer->project_id ?? 'no_project';
            $projectName = $timer->project->name ?? 'No Project';
            if (!isset($projectBreakdown[$projectKey])) {
                $projectBreakdown[$projectKey] = [
                    'name' => $projectName,
                    'duration' => 0,
                    'amount' => 0,
                    'count' => 0,
                ];
            }
            $projectBreakdown[$projectKey]['duration'] += $duration;
            $projectBreakdown[$projectKey]['amount'] += $timer->calculated_amount ?? 0;
            $projectBreakdown[$projectKey]['count']++;

            // Task breakdown
            if ($timer->task_id) {
                $taskKey = $timer->task_id;
                $taskName = $timer->task->name ?? 'Unknown Task';
                if (!isset($taskBreakdown[$taskKey])) {
                    $taskBreakdown[$taskKey] = [
                        'name' => $taskName,
                        'duration' => 0,
                        'amount' => 0,
                        'count' => 0,
                    ];
                }
                $taskBreakdown[$taskKey]['duration'] += $duration;
                $taskBreakdown[$taskKey]['amount'] += $timer->calculated_amount ?? 0;
                $taskBreakdown[$taskKey]['count']++;
            }

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

        // Get active timer if exists
        $activeTimer = Timer::where('user_id', $userId)
            ->whereIn('status', ['running', 'paused'])
            ->first();

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
            'active_timer' => $activeTimer ? [
                'id' => $activeTimer->id,
                'status' => $activeTimer->status,
                'duration' => $activeTimer->duration,
                'duration_formatted' => $activeTimer->duration_formatted,
                'calculated_amount' => $activeTimer->calculated_amount,
            ] : null,
            'breakdowns' => [
                'by_project' => array_values($projectBreakdown),
                'by_task' => array_values($taskBreakdown),
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
    public function getAvailableBillingRates(User $user, ?int $projectId = null): Collection
    {
        // This would be expanded to include project-specific rates,
        // account-level rates, and user-specific overrides
        return DB::table('billing_rates')
            ->where(function ($query) use ($user, $projectId) {
                $query->where('user_id', $user->id)
                    ->orWhere('account_id', $user->account_id)
                    ->orWhere(function ($q) use ($projectId) {
                        if ($projectId) {
                            $q->where('project_id', $projectId);
                        }
                    })
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
        $currentDuration = $timer->duration;
        if ($currentDuration > 0 && $currentAmount > 0) {
            $pace = $currentAmount / ($currentDuration / 3600); // $ per hour
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