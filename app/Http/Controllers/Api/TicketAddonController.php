<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketAddon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketAddonController extends Controller
{
    /**
     * Display addons for a specific ticket
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get ticket ID from request
        $ticketId = $request->get('ticket_id');

        if ($ticketId) {
            // Get addons for specific ticket
            $ticket = Ticket::findOrFail($ticketId);
            $this->authorize('view', $ticket);

            $addons = $ticket->addons()
                ->with(['addedBy:id,name', 'approvedBy:id,name', 'template:id,name'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Get all addons user can access
            $query = TicketAddon::with(['ticket', 'addedBy:id,name', 'approvedBy:id,name'])
                ->whereHas('ticket', function ($q) use ($user) {
                    if (! $user->hasAnyPermission(['tickets.view.all', 'admin.read'])) {
                        $q->where('assigned_to', $user->id)
                            ->orWhere('created_by', $user->id);
                    }
                });

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            $addons = $query->orderBy('created_at', 'desc')
                ->paginate(20);
        }

        return response()->json([
            'data' => $addons,
            'message' => 'Ticket addons retrieved successfully',
        ]);
    }

    /**
     * Store a newly created addon
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:product,service,expense,license,hardware,software,other',
            'sku' => 'nullable|string|max:100',
            'unit_price' => 'required|numeric|min:0|max:999999.99',
            'quantity' => 'required|numeric|min:0.01|max:99999.99',
            'discount_amount' => 'nullable|numeric|min:0|max:999999.99',
            'billable' => 'boolean',
            'is_taxable' => 'boolean',
            'billing_category' => 'required|string|in:addon,expense,product,service',
            'addon_template_id' => 'nullable|exists:addon_templates,id',
            'metadata' => 'nullable|array',
        ]);

        // Check permissions on the ticket
        $ticket = Ticket::findOrFail($validated['ticket_id']);
        $this->authorize('update', $ticket);

        // Add user context
        $validated['added_by_user_id'] = $user->id;

        // Set defaults
        $validated['discount_amount'] = $validated['discount_amount'] ?? 0.00;
        $validated['billable'] = $validated['billable'] ?? true;
        $validated['is_taxable'] = $validated['is_taxable'] ?? true;

        // Set status as pending for approval workflow
        $validated['status'] = 'pending';

        $addon = TicketAddon::create($validated);
        $addon->load(['addedBy:id,name', 'template:id,name']);

        return response()->json([
            'data' => $addon,
            'message' => 'Addon created successfully',
        ], 201);
    }

    /**
     * Display the specified addon
     */
    public function show(TicketAddon $ticketAddon): JsonResponse
    {
        $this->authorize('view', $ticketAddon->ticket);

        $ticketAddon->load([
            'ticket:id,ticket_number,title',
            'addedBy:id,name',
            'approvedBy:id,name',
            'template:id,name,category',
        ]);

        return response()->json([
            'data' => $ticketAddon,
            'message' => 'Addon retrieved successfully',
        ]);
    }

    /**
     * Update the specified addon
     */
    public function update(Request $request, TicketAddon $ticketAddon): JsonResponse
    {
        $this->authorize('update', $ticketAddon->ticket);

        // Can only edit pending or rejected addons
        if (! $ticketAddon->canBeEdited()) {
            return response()->json([
                'message' => 'This addon cannot be edited in its current status',
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|string|in:product,service,expense,license,hardware,software,other',
            'sku' => 'nullable|string|max:100',
            'unit_price' => 'sometimes|numeric|min:0|max:999999.99',
            'quantity' => 'sometimes|numeric|min:0.01|max:99999.99',
            'discount_amount' => 'nullable|numeric|min:0|max:999999.99',
            'billable' => 'boolean',
            'is_taxable' => 'boolean',
            'billing_category' => 'sometimes|string|in:addon,expense,product,service',
            'metadata' => 'nullable|array',
        ]);

        $ticketAddon->update($validated);
        $ticketAddon->load(['addedBy:id,name', 'approvedBy:id,name', 'template:id,name']);

        return response()->json([
            'data' => $ticketAddon,
            'message' => 'Addon updated successfully',
        ]);
    }

    /**
     * Remove the specified addon
     */
    public function destroy(TicketAddon $ticketAddon): JsonResponse
    {
        $this->authorize('update', $ticketAddon->ticket);

        // Can only delete pending or rejected addons
        if (! $ticketAddon->canBeEdited()) {
            return response()->json([
                'message' => 'This addon cannot be deleted in its current status',
            ], 422);
        }

        $ticketAddon->delete();

        return response()->json([
            'message' => 'Addon deleted successfully',
        ]);
    }

    /**
     * Approve an addon
     */
    public function approve(Request $request, TicketAddon $ticketAddon): JsonResponse
    {
        $user = $request->user();
        $this->authorize('update', $ticketAddon->ticket);

        // Check if user has approval permissions
        if (! $user->hasAnyPermission(['tickets.approve', 'admin.write'])) {
            return response()->json([
                'message' => 'Insufficient permissions to approve addons',
            ], 403);
        }

        if (! $ticketAddon->canBeApproved()) {
            return response()->json([
                'message' => 'This addon cannot be approved in its current status',
            ], 422);
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        $success = $ticketAddon->approve($user, $validated['approval_notes'] ?? null);

        if ($success) {
            $ticketAddon->load(['addedBy:id,name', 'approvedBy:id,name']);

            return response()->json([
                'data' => $ticketAddon,
                'message' => 'Addon approved successfully',
            ]);
        }

        return response()->json([
            'message' => 'Failed to approve addon',
        ], 422);
    }

    /**
     * Reject an addon
     */
    public function reject(Request $request, TicketAddon $ticketAddon): JsonResponse
    {
        $user = $request->user();
        $this->authorize('update', $ticketAddon->ticket);

        // Check if user has approval permissions
        if (! $user->hasAnyPermission(['tickets.approve', 'admin.write'])) {
            return response()->json([
                'message' => 'Insufficient permissions to reject addons',
            ], 403);
        }

        if (! $ticketAddon->canBeApproved()) {
            return response()->json([
                'message' => 'This addon cannot be rejected in its current status',
            ], 422);
        }

        $validated = $request->validate([
            'approval_notes' => 'required|string|max:1000',
        ]);

        $success = $ticketAddon->reject($user, $validated['approval_notes']);

        if ($success) {
            $ticketAddon->load(['addedBy:id,name', 'approvedBy:id,name']);

            return response()->json([
                'data' => $ticketAddon,
                'message' => 'Addon rejected successfully',
            ]);
        }

        return response()->json([
            'message' => 'Failed to reject addon',
        ], 422);
    }

    /**
     * Unapprove an addon (only if not invoiced)
     */
    public function unapprove(Request $request, TicketAddon $ticketAddon): JsonResponse
    {
        $user = $request->user();
        $this->authorize('update', $ticketAddon->ticket);

        // Check if user has approval permissions
        if (! $user->hasAnyPermission(['tickets.approve', 'admin.write'])) {
            return response()->json([
                'message' => 'Insufficient permissions to unapprove addons',
            ], 403);
        }

        // Check if the addon can be unapproved
        if (! $ticketAddon->canUnapprove()) {
            if ($ticketAddon->isInvoiced()) {
                return response()->json([
                    'message' => 'Cannot unapprove addon that has been invoiced',
                ], 422);
            }
            if ($ticketAddon->status !== 'approved') {
                return response()->json([
                    'message' => 'Addon is not approved',
                ], 422);
            }
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $success = $ticketAddon->unapprove($user, $validated['notes'] ?? 'Unapproved and returned to pending status');

        if ($success) {
            $ticketAddon->load(['addedBy:id,name', 'approvedBy:id,name']);

            return response()->json([
                'data' => $ticketAddon,
                'message' => 'Addon unapproved successfully and returned to pending status',
            ]);
        }

        return response()->json([
            'message' => 'Failed to unapprove addon',
        ], 422);
    }

    /**
     * Get addons for a specific ticket
     */
    public function forTicket(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        if (! $ticket->canBeViewedBy($user)) {
            return response()->json(['error' => 'Cannot view this ticket.'], 403);
        }

        $query = $ticket->addons()
            ->with(['addedBy:id,name', 'approvedBy:id,name']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('billable')) {
            $billable = $request->boolean('billable');
            if ($billable) {
                $query->where('status', 'approved');
            }
        }

        $addons = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $addons]);
    }

    /**
     * Mark an addon as complete
     */
    public function complete(Request $request, TicketAddon $ticketAddon): JsonResponse
    {
        $user = $request->user();

        if (! $ticketAddon->ticket->canBeEditedBy($user)) {
            return response()->json(['error' => 'Cannot edit this ticket.'], 403);
        }

        if ($ticketAddon->status !== 'approved') {
            return response()->json([
                'error' => 'Only approved add-ons can be marked as complete.',
            ], 422);
        }

        $validated = $request->validate([
            'completion_notes' => 'nullable|string|max:1000',
            'actual_hours' => 'nullable|numeric|min:0|max:1000',
            'actual_cost' => 'nullable|numeric|min:0|max:999999.99',
        ]);

        $ticketAddon->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $user->id,
            'completion_notes' => $validated['completion_notes'] ?? null,
            'actual_hours' => $validated['actual_hours'] ?? $ticketAddon->actual_hours,
            'actual_cost' => $validated['actual_cost'] ?? $ticketAddon->actual_cost,
        ]);

        // Log activity
        $ticketAddon->ticket->activities()->create([
            'user_id' => $user->id,
            'type' => 'addon',
            'description' => "Completed add-on: {$ticketAddon->title}",
            'details' => [
                'addon_id' => $ticketAddon->id,
                'action' => 'completed',
                'title' => $ticketAddon->title,
                'actual_hours' => $ticketAddon->actual_hours,
                'actual_cost' => $ticketAddon->actual_cost,
                'notes' => $validated['completion_notes'] ?? null,
            ],
        ]);

        return response()->json([
            'data' => $ticketAddon->fresh(),
            'message' => 'Add-on marked as complete.',
        ]);
    }

    /**
     * Bulk approve addons
     */
    public function bulkApprove(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has approval permissions
        if (! $user->hasAnyPermission(['tickets.approve', 'admin.write'])) {
            return response()->json([
                'message' => 'Insufficient permissions to approve addons',
            ], 403);
        }

        $validated = $request->validate([
            'ticket_addon_ids' => 'required|array',
            'ticket_addon_ids.*' => 'exists:ticket_addons,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $addonIds = $validated['ticket_addon_ids'];
        $notes = $validated['notes'] ?? null;

        // Get addons that can be approved
        $addons = TicketAddon::whereIn('id', $addonIds)
            ->where('status', 'pending')
            ->get();

        $approvedCount = 0;
        $errors = [];

        foreach ($addons as $addon) {
            try {
                // Check ticket permissions
                if (! $addon->ticket->canBeEditedBy($user)) {
                    $errors[] = "No permission to approve addon: {$addon->name}";

                    continue;
                }

                if ($addon->approve($user, $notes)) {
                    $approvedCount++;
                } else {
                    $errors[] = "Failed to approve addon: {$addon->name}";
                }
            } catch (\Exception $e) {
                $errors[] = "Error approving addon {$addon->name}: ".$e->getMessage();
            }
        }

        $message = "Approved {$approvedCount} addon(s)";
        if (! empty($errors)) {
            $message .= '. Errors: '.implode(', ', $errors);
        }

        return response()->json([
            'message' => $message,
            'approved_count' => $approvedCount,
            'errors' => $errors,
        ]);
    }

    /**
     * Bulk reject addons
     */
    public function bulkReject(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has approval permissions
        if (! $user->hasAnyPermission(['tickets.approve', 'admin.write'])) {
            return response()->json([
                'message' => 'Insufficient permissions to reject addons',
            ], 403);
        }

        $validated = $request->validate([
            'ticket_addon_ids' => 'required|array',
            'ticket_addon_ids.*' => 'exists:ticket_addons,id',
            'notes' => 'required|string|max:1000',
        ]);

        $addonIds = $validated['ticket_addon_ids'];
        $notes = $validated['notes'];

        // Get addons that can be rejected
        $addons = TicketAddon::whereIn('id', $addonIds)
            ->where('status', 'pending')
            ->get();

        $rejectedCount = 0;
        $errors = [];

        foreach ($addons as $addon) {
            try {
                // Check ticket permissions
                if (! $addon->ticket->canBeEditedBy($user)) {
                    $errors[] = "No permission to reject addon: {$addon->name}";

                    continue;
                }

                if ($addon->reject($user, $notes)) {
                    $rejectedCount++;
                } else {
                    $errors[] = "Failed to reject addon: {$addon->name}";
                }
            } catch (\Exception $e) {
                $errors[] = "Error rejecting addon {$addon->name}: ".$e->getMessage();
            }
        }

        $message = "Rejected {$rejectedCount} addon(s)";
        if (! empty($errors)) {
            $message .= '. Errors: '.implode(', ', $errors);
        }

        return response()->json([
            'message' => $message,
            'rejected_count' => $rejectedCount,
            'errors' => $errors,
        ]);
    }
}
