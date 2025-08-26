<?php

namespace App\Events;

use App\Models\ImportJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImportProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ImportJob $job;

    /**
     * Create a new event instance.
     */
    public function __construct(ImportJob $job)
    {
        $this->job = $job;
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
            'progress' => $this->job->progress,
            'progress_percentage' => $this->job->progress_percentage,
            'current_operation' => $this->job->current_operation,
            'records_processed' => $this->job->records_processed,
            'records_imported' => $this->job->records_imported,
            'records_updated' => $this->job->records_updated,
            'records_skipped' => $this->job->records_skipped,
            'records_failed' => $this->job->records_failed,
            'status' => $this->job->status,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'import.progress.updated';
    }
}
