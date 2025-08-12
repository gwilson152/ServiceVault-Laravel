<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketStatusController extends Controller
{
    /**
     * Display a listing of ticket statuses
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Only admins can view all statuses
        if (!$user->hasAnyPermission(['admin.read', 'settings.manage', 'tickets.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to view ticket statuses'
            ], 403);
        }

        $query = TicketStatus::query();

        // Filter by active status
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Filter by closed status
        if ($request->filled('closed')) {
            $query->where('is_closed', $request->boolean('closed'));
        }

        $statuses = $query->ordered()->get();

        return response()->json([
            'data' => $statuses,
            'workflow_transitions' => TicketStatus::getWorkflowTransitions(),
            'message' => 'Ticket statuses retrieved successfully'
        ]);
    }

    /**
     * Get status options for forms
     */
    public function options(Request $request): JsonResponse
    {
        $options = TicketStatus::getOptions();
        
        return response()->json([
            'options' => $options,
            'default_status' => TicketStatus::getDefault()?->key
        ]);
    }

    /**
     * Store a newly created ticket status (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyPermission(['admin.write', 'settings.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to create ticket statuses'
            ], 403);
        }

        $validated = $request->validate([
            'key' => 'required|string|max:50|unique:ticket_statuses,key|alpha_dash',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'bg_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'required|string|max:50',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'is_closed' => 'boolean',
            'is_billable' => 'boolean',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        // Set defaults
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['is_default'] = $validated['is_default'] ?? false;
        $validated['is_closed'] = $validated['is_closed'] ?? false;
        $validated['is_billable'] = $validated['is_billable'] ?? true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $status = TicketStatus::create($validated);

        return response()->json([
            'data' => $status,
            'message' => 'Ticket status created successfully'
        ], 201);
    }

    /**
     * Display the specified ticket status
     */
    public function show(TicketStatus $ticketStatus): JsonResponse
    {
        return response()->json([
            'data' => $ticketStatus,
            'next_statuses' => $ticketStatus->getNextStatuses(),
            'message' => 'Ticket status retrieved successfully'
        ]);
    }

    /**
     * Update the specified ticket status (Admin only)
     */
    public function update(Request $request, TicketStatus $ticketStatus): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyPermission(['admin.write', 'settings.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to update ticket statuses'
            ], 403);
        }

        $validated = $request->validate([
            'key' => [
                'sometimes',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('ticket_statuses', 'key')->ignore($ticketStatus->id)
            ],
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'bg_color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'sometimes|string|max:50',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'is_closed' => 'boolean',
            'is_billable' => 'boolean',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        $ticketStatus->update($validated);

        return response()->json([
            'data' => $ticketStatus,
            'message' => 'Ticket status updated successfully'
        ]);
    }

    /**
     * Remove the specified ticket status (Admin only)
     */
    public function destroy(TicketStatus $ticketStatus): JsonResponse
    {
        $user = request()->user();
        
        if (!$user->hasAnyPermission(['admin.write', 'settings.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to delete ticket statuses'
            ], 403);
        }

        // Check if status is being used
        if ($ticketStatus->tickets()->exists()) {
            return response()->json([
                'message' => 'Cannot delete status that is being used by existing tickets'
            ], 422);
        }

        // Prevent deletion of default status
        if ($ticketStatus->is_default) {
            return response()->json([
                'message' => 'Cannot delete the default ticket status'
            ], 422);
        }

        $ticketStatus->delete();

        return response()->json([
            'message' => 'Ticket status deleted successfully'
        ]);
    }

    /**
     * Get workflow transitions for a specific status
     */
    public function transitions(TicketStatus $ticketStatus): JsonResponse
    {
        $nextStatuses = $ticketStatus->getNextStatuses();
        $allTransitions = TicketStatus::getWorkflowTransitions();

        return response()->json([
            'current_status' => $ticketStatus,
            'next_statuses' => $nextStatuses,
            'all_transitions' => $allTransitions
        ]);
    }

    /**
     * Reorder ticket statuses
     */
    public function reorder(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyPermission(['admin.write', 'settings.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to reorder ticket statuses'
            ], 403);
        }

        $validated = $request->validate([
            'statuses' => 'required|array',
            'statuses.*.id' => 'required|string|exists:ticket_statuses,id',
            'statuses.*.sort_order' => 'required|integer|min:0'
        ]);

        foreach ($validated['statuses'] as $statusData) {
            TicketStatus::where('id', $statusData['id'])
                ->update(['sort_order' => $statusData['sort_order']]);
        }

        $statuses = TicketStatus::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $statuses,
            'message' => 'Ticket statuses reordered successfully'
        ]);
    }
}
