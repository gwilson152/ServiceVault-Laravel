<?php

namespace App\Events;

use App\Models\ImportJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportJobStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ImportJob $job;

    public string $eventType;

    /**
     * Create a new event instance.
     */
    public function __construct(ImportJob $job, string $eventType = 'status_changed')
    {
        $this->job = $job;
        $this->eventType = $eventType;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('import.job.'.$this->job->id),
            new PrivateChannel('import.profile.'.$this->job->profile_id),
            new PrivateChannel('user.'.$this->job->created_by),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->job->id,
            'status' => $this->job->status,
            'event_type' => $this->eventType,
            'progress_percentage' => $this->job->progress_percentage,
            'current_operation' => $this->job->current_operation,
            'records_processed' => $this->job->records_processed,
            'records_imported' => $this->job->records_imported,
            'records_updated' => $this->job->records_updated,
            'records_skipped' => $this->job->records_skipped,
            'records_failed' => $this->job->records_failed,
            'started_at' => $this->job->started_at?->toISOString(),
            'completed_at' => $this->job->completed_at?->toISOString(),
            'errors' => $this->job->errors,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'import.job.status.changed';
    }
}
