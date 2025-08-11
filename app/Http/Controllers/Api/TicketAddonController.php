<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketAddon;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class TicketAddonController extends Controller
{
    /**
     * Display addons for a specific ticket
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get ticket ID from request
        $ticketId = $request->get('service_ticket_id');
        
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
                    if (!$user->hasAnyPermission(['tickets.view.all', 'admin.read'])) {
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
            'message' => 'Ticket addons retrieved successfully'
        ]);
    }

    /**
     * Store a newly created addon
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'service_ticket_id' => 'required|exists:service_tickets,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:product,service,expense,license,hardware,software,other',
            'sku' => 'nullable|string|max:100',
            'unit_price' => 'required|numeric|min:0|max:999999.99',
            'quantity' => 'required|numeric|min:0.01|max:99999.99',
            'discount_amount' => 'nullable|numeric|min:0|max:999999.99',
            'tax_rate' => 'nullable|numeric|min:0|max:1',
            'is_billable' => 'boolean',
            'is_taxable' => 'boolean',
            'billing_category' => 'required|string|in:addon,expense,product,service',
            'addon_template_id' => 'nullable|exists:addon_templates,id',
            'metadata' => 'nullable|array'
        ]);
        
        // Check permissions on the ticket
        $ticket = Ticket::findOrFail($validated['service_ticket_id']);
        $this->authorize('update', $ticket);
        
        // Add user context
        $validated['added_by_user_id'] = $user->id;
        
        // Set defaults
        $validated['discount_amount'] = $validated['discount_amount'] ?? 0.00;
        $validated['tax_rate'] = $validated['tax_rate'] ?? 0.0000;
        $validated['is_billable'] = $validated['is_billable'] ?? true;
        $validated['is_taxable'] = $validated['is_taxable'] ?? true;
        
        $addon = TicketAddon::create($validated);
        $addon->load(['addedBy:id,name', 'template:id,name']);
        
        return response()->json([
            'data' => $addon,
            'message' => 'Addon created successfully'
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
            'template:id,name,category'
        ]);
        
        return response()->json([
            'data' => $ticketAddon,
            'message' => 'Addon retrieved successfully'
        ]);
    }

    /**
     * Update the specified addon
     */
    public function update(Request $request, TicketAddon $ticketAddon): JsonResponse
    {
        $this->authorize('update', $ticketAddon->ticket);
        
        // Can only edit pending or rejected addons
        if (!$ticketAddon->canBeEdited()) {
            return response()->json([
                'message' => 'This addon cannot be edited in its current status'
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
            'tax_rate' => 'nullable|numeric|min:0|max:1',
            'is_billable' => 'boolean',
            'is_taxable' => 'boolean',
            'billing_category' => 'sometimes|string|in:addon,expense,product,service',
            'metadata' => 'nullable|array'
        ]);
        
        $ticketAddon->update($validated);
        $ticketAddon->load(['addedBy:id,name', 'approvedBy:id,name', 'template:id,name']);
        
        return response()->json([
            'data' => $ticketAddon,
            'message' => 'Addon updated successfully'
        ]);
    }

    /**
     * Remove the specified addon
     */
    public function destroy(TicketAddon $ticketAddon): JsonResponse
    {
        $this->authorize('update', $ticketAddon->ticket);
        
        // Can only delete pending or rejected addons
        if (!$ticketAddon->canBeEdited()) {
            return response()->json([
                'message' => 'This addon cannot be deleted in its current status'
            ], 422);
        }
        
        $ticketAddon->delete();
        
        return response()->json([
            'message' => 'Addon deleted successfully'
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
        if (!$user->hasAnyPermission(['tickets.approve', 'admin.write'])) {
            return response()->json([
                'message' => 'Insufficient permissions to approve addons'
            ], 403);
        }
        
        if (!$ticketAddon->canBeApproved()) {
            return response()->json([
                'message' => 'This addon cannot be approved in its current status'
            ], 422);
        }
        
        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:1000'
        ]);
        
        $success = $ticketAddon->approve($user, $validated['approval_notes'] ?? null);
        
        if ($success) {
            $ticketAddon->load(['addedBy:id,name', 'approvedBy:id,name']);
            
            return response()->json([
                'data' => $ticketAddon,
                'message' => 'Addon approved successfully'
            ]);
        }
        
        return response()->json([
            'message' => 'Failed to approve addon'
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
        if (!$user->hasAnyPermission(['tickets.approve', 'admin.write'])) {
            return response()->json([
                'message' => 'Insufficient permissions to reject addons'
            ], 403);
        }
        
        if (!$ticketAddon->canBeApproved()) {
            return response()->json([
                'message' => 'This addon cannot be rejected in its current status'
            ], 422);
        }
        
        $validated = $request->validate([
            'approval_notes' => 'required|string|max:1000'
        ]);
        
        $success = $ticketAddon->reject($user, $validated['approval_notes']);
        
        if ($success) {
            $ticketAddon->load(['addedBy:id,name', 'approvedBy:id,name']);
            
            return response()->json([
                'data' => $ticketAddon,
                'message' => 'Addon rejected successfully'
            ]);
        }
        
        return response()->json([
            'message' => 'Failed to reject addon'
        ], 422);
    }
}
