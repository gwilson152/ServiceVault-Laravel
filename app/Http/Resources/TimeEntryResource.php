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
            'duration_formatted' => $this->formatDuration($this->duration), // Duration is stored in minutes
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'billable' => $this->billable,
            'status' => $this->status,
            'notes' => $this->notes,
            'approval_notes' => $this->approval_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'user' => $this->whenLoaded('user', function () use ($request) {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->when(
                        $request->user()->id === $this->user_id ||
                        $request->user()->hasPermission('admin.manage'),
                        $this->user->email
                    ),
                ];
            }),

            'account' => $this->whenLoaded('account', function () {
                return [
                    'id' => $this->account->id,
                    'name' => $this->account->name,
                ];
            }),

            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'name' => $this->project->name,
                    'description' => $this->when(
                        isset($this->project->description),
                        $this->project->description
                    ),
                ];
            }),

            'billing_rate' => $this->whenLoaded('billingRate', function () {
                return [
                    'id' => $this->billingRate->id,
                    'rate' => $this->billingRate->rate,
                ];
            }),

            'approved_by' => $this->whenLoaded('approvedBy', function () {
                return [
                    'id' => $this->approvedBy->id,
                    'name' => $this->approvedBy->name,
                ];
            }),

            'approved_at' => $this->approved_at,

            // Invoice information
            'is_invoiced' => $this->isInvoiced(),
            'can_unapprove' => $this->canUnapprove(),

            // Calculated fields
            'hours' => round($this->duration / 60, 2), // Convert minutes to hours
            'calculated_cost' => $this->when(
                $this->relationLoaded('billingRate') && $this->billingRate,
                function () {
                    return round(($this->duration / 60) * $this->billingRate->rate, 2); // Convert minutes to hours
                }
            ),

            // Permission flags for frontend
            'can_edit' => $this->canEdit($request->user()),
            'can_delete' => $this->canDelete($request->user()),
            'can_approve' => $this->canApprove($request->user()),
        ];
    }

    /**
     * Format duration in minutes to human readable format
     */
    private function formatDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $remainingMinutes);
        }

        return sprintf('%dm', $remainingMinutes);
    }

    /**
     * Check if user can edit this time entry
     */
    private function canEdit($user): bool
    {
        // Can't edit non-pending entries
        if ($this->status !== 'pending') {
            return false;
        }

        // Original creator can always edit their own pending entries
        if ($this->user_id === $user->id) {
            return true;
        }

        // Service providers and users with time management permissions can edit time entries
        if ($user->user_type === 'service_provider' ||
            $user->hasAnyPermission(['time.manage', 'time.edit.all', 'admin.manage', 'admin.write'])) {
            return true;
        }

        // Team managers can edit entries from their team members
        if ($user->hasPermission('time.edit.team') || $user->hasPermission('teams.manage')) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can delete this time entry
     */
    private function canDelete($user): bool
    {
        // Can only delete pending entries
        if ($this->status !== 'pending') {
            return false;
        }

        // Original creator can always delete their own pending entries
        if ($this->user_id === $user->id) {
            return true;
        }

        // Service providers and managers can delete time entries
        if ($user->user_type === 'service_provider' ||
            $user->hasAnyPermission(['time.manage', 'time.delete.all', 'admin.manage', 'admin.write'])) {
            return true;
        }

        // Team managers can delete entries from their team members
        if ($user->hasPermission('time.edit.team') || $user->hasPermission('teams.manage')) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can approve this time entry
     */
    private function canApprove($user): bool
    {
        // Entry must be pending
        if ($this->status !== 'pending') {
            return false;
        }

        // Can't approve your own entries
        if ($this->user_id === $user->id) {
            return false;
        }

        // Service providers, managers, and admins can approve
        if ($user->user_type === 'service_provider' ||
            $user->hasAnyPermission(['time.manage', 'time.approve', 'teams.manage', 'admin.manage', 'admin.write'])) {
            return true;
        }

        return false;
    }
}
