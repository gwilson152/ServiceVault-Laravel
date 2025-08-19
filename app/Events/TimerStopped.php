<?php

namespace App\Events;

use App\Models\Timer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimerStopped implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Timer $timer;

    /**
     * Create a new event instance.
     */
    public function __construct(Timer $timer)
    {
        $this->timer = $timer;
        $this->timer->load(['billingRate', 'timeEntry']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->timer->user_id),
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
                'stopped_at' => $this->timer->stopped_at?->toIso8601String(),
                'duration' => $this->timer->duration,
                'duration_formatted' => $this->timer->duration_formatted,
                'calculated_amount' => $this->timer->calculated_amount,
                'time_entry_id' => $this->timer->time_entry_id,
                'device_id' => $this->timer->device_id,
            ],
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'timer.stopped';
    }
}
