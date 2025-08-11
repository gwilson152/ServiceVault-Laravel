<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            // Note: Project and task references removed - these entities are no longer used in this system
            'billing_rate' => $this->whenLoaded('billingRate', function () {
                return [
                    'id' => $this->billingRate->id,
                    'name' => $this->billingRate->name,
                    'rate' => $this->billingRate->rate,
                ];
            }),
            'ticket' => $this->whenLoaded('ticket', function () {
                return [
                    'id' => $this->ticket->id,
                    'ticket_number' => $this->ticket->ticket_number,
                    'title' => $this->ticket->title,
                    'status' => $this->ticket->status,
                    'priority' => $this->ticket->priority,
                ];
            }),
            'time_entry' => $this->whenLoaded('timeEntry', function () {
                return [
                    'id' => $this->timeEntry->id,
                    'status' => $this->timeEntry->status,
                ];
            }),
            'description' => $this->description,
            'status' => $this->status,
            'started_at' => $this->started_at?->toIso8601String(),
            'stopped_at' => $this->stopped_at?->toIso8601String(),
            'paused_at' => $this->paused_at?->toIso8601String(),
            'total_paused_duration' => $this->total_paused_duration,
            'duration' => $this->duration,
            'duration_formatted' => $this->duration_formatted,
            'is_running' => $this->is_running,
            'is_paused' => $this->is_paused,
            'calculated_amount' => $this->calculated_amount,
            'device_id' => $this->device_id,
            'is_synced' => $this->is_synced,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}