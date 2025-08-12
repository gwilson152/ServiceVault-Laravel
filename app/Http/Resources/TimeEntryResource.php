<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeEntryResource extends JsonResource
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
            'description' => $this->description,
            'duration' => $this->duration,
            'duration_formatted' => $this->formatDuration($this->duration),
            'date' => $this->date,
            'billable' => $this->billable,
            'status' => $this->status,
            'notes' => $this->notes,
            'approval_notes' => $this->approval_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->when(
                        $request->user()->id === $this->user_id ||
                        $request->user()->hasPermission('admin.manage'),
                        $this->user->email
                    )
                ];
            }),
            
            'account' => $this->whenLoaded('account', function () {
                return [
                    'id' => $this->account->id,
                    'name' => $this->account->name
                ];
            }),
            
            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'name' => $this->project->name,
                    'description' => $this->when(
                        isset($this->project->description),
                        $this->project->description
                    )
                ];
            }),
            
            'billing_rate' => $this->whenLoaded('billingRate', function () {
                return [
                    'id' => $this->billingRate->id,
                    'rate' => $this->billingRate->rate,
                    'currency' => $this->billingRate->currency ?? 'USD',
                    'type' => $this->billingRate->type
                ];
            }),
            
            'approved_by' => $this->whenLoaded('approvedBy', function () {
                return [
                    'id' => $this->approvedBy->id,
                    'name' => $this->approvedBy->name
                ];
            }),
            
            'approved_at' => $this->approved_at,
            
            // Calculated fields
            'hours' => round($this->duration / 3600, 2),
            'cost' => $this->when(
                $this->relationLoaded('billingRate') && $this->billingRate,
                function () {
                    return round(($this->duration / 3600) * $this->billingRate->rate, 2);
                }
            ),
            
            // Permission flags for frontend
            'can_edit' => $this->canEdit($request->user()),
            'can_delete' => $this->canDelete($request->user()),
            'can_approve' => $this->canApprove($request->user()),
        ];
    }
    
    /**
     * Format duration in seconds to human readable format
     */
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $minutes);
        }
        
        return sprintf('%dm', $minutes);
    }
    
    /**
     * Check if user can edit this time entry
     */
    private function canEdit($user): bool
    {
        // Users can edit their own pending entries
        if ($this->user_id === $user->id && $this->status === 'pending') {
            return true;
        }
        
        // Managers and admins can edit any entries
        if ($user->hasAnyPermission(['teams.manage', 'admin.manage'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if user can delete this time entry
     */
    private function canDelete($user): bool
    {
        // Users can delete their own pending entries
        if ($this->user_id === $user->id && $this->status === 'pending') {
            return true;
        }
        
        // Managers and admins can delete any entries
        if ($user->hasAnyPermission(['teams.manage', 'admin.manage'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if user can approve this time entry
     */
    private function canApprove($user): bool
    {
        // Only managers and admins can approve
        if (!$user->hasAnyPermission(['teams.manage', 'admin.manage'])) {
            return false;
        }
        
        // Can't approve your own entries
        if ($this->user_id === $user->id) {
            return false;
        }
        
        // Entry must be pending
        if ($this->status !== 'pending') {
            return false;
        }
        
        return true;
    }
}
