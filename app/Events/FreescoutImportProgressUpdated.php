<?php

namespace App\Events;

use App\Models\ImportJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FreescoutImportProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ImportJob $job;
    public string $currentStep;
    public array $stepDetails;

    /**
     * Create a new event instance.
     */
    public function __construct(ImportJob $job, string $currentStep, array $stepDetails = [])
    {
        $this->job = $job;
        $this->currentStep = $currentStep;
        $this->stepDetails = $stepDetails;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('import.freescout.job.'.$this->job->id),
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
            'progress' => $this->job->progress,
            'message' => $this->job->status_message,
            'current_step' => $this->currentStep,
            'step_details' => $this->stepDetails,
            'import_type' => 'freescout',
            'records_processed' => $this->job->records_processed,
            'records_imported' => $this->job->records_imported,
            'records_updated' => $this->job->records_updated,
            'records_skipped' => $this->job->records_skipped,
            'records_failed' => $this->job->records_failed,
            'started_at' => $this->job->started_at?->toISOString(),
            'error_log' => $this->job->error_log,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'freescout.import.progress';
    }
}