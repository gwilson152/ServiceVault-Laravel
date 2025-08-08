<?php

namespace App\Events;

use App\Models\Timer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimerUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Timer $timer;

    /**
     * Create a new event instance.
     */
    public function __construct(Timer $timer)
    {
        $this->timer = $timer;
        $this->timer->load(['project', 'task', 'billingRate']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->timer->user_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'timer' => [
                'id' => $this->timer->id,
                'status' => $this->timer->status,
                'description' => $this->timer->description,
                'started_at' => $this->timer->started_at->toIso8601String(),
                'paused_at' => $this->timer->paused_at?->toIso8601String(),
                'duration' => $this->timer->duration,
                'duration_formatted' => $this->timer->duration_formatted,
                'calculated_amount' => $this->timer->calculated_amount,
                'project' => $this->timer->project ? [
                    'id' => $this->timer->project->id,
                    'name' => $this->timer->project->name,
                ] : null,
                'task' => $this->timer->task ? [
                    'id' => $this->timer->task->id,
                    'name' => $this->timer->task->name,
                ] : null,
                'billing_rate' => $this->timer->billingRate ? [
                    'id' => $this->timer->billingRate->id,
                    'name' => $this->timer->billingRate->name,
                    'rate' => $this->timer->billingRate->rate,
                ] : null,
                'device_id' => $this->timer->device_id,
                'metadata' => $this->timer->metadata,
            ],
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'timer.updated';
    }
}