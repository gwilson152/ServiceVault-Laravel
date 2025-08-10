<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AddonTemplate;
use App\Models\ServiceTicket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddonTemplateController extends Controller
{
    /**
     * Display a listing of addon templates
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Only users who can create addons should see templates
        if (!$user->hasAnyPermission(['tickets.create', 'tickets.edit', 'admin.write'])) {
            return response()->json([
                'message' => 'Insufficient permissions to view addon templates'
            ], 403);
        }
        
        $query = AddonTemplate::active()->ordered();
        
        // Filter by category if provided
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }
        
        $templates = $query->get();
        
        return response()->json([
            'data' => $templates,
            'categories' => AddonTemplate::getCategories(),
            'message' => 'Addon templates retrieved successfully'
        ]);
    }

    /**
     * Store a newly created template (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyPermission(['admin.write', 'system.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to create addon templates'
            ], 403);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|in:product,service,license,hardware,software,expense,other',
            'sku' => 'nullable|string|max:100|unique:addon_templates,sku',
            'default_unit_price' => 'required|numeric|min:0|max:999999.99',
            'default_quantity' => 'required|numeric|min:0.01|max:99999.99',
            'allow_quantity_override' => 'boolean',
            'allow_price_override' => 'boolean',
            'is_billable' => 'boolean',
            'is_taxable' => 'boolean',
            'billing_category' => 'required|string|in:addon,expense,product,service',
            'default_tax_rate' => 'nullable|numeric|min:0|max:1',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|array'
        ]);
        
        // Set defaults
        $validated['allow_quantity_override'] = $validated['allow_quantity_override'] ?? true;
        $validated['allow_price_override'] = $validated['allow_price_override'] ?? false;
        $validated['is_billable'] = $validated['is_billable'] ?? true;
        $validated['is_taxable'] = $validated['is_taxable'] ?? true;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['default_tax_rate'] = $validated['default_tax_rate'] ?? 0.0000;
        
        $template = AddonTemplate::create($validated);
        
        return response()->json([
            'data' => $template,
            'message' => 'Addon template created successfully'
        ], 201);
    }

    /**
     * Display the specified template
     */
    public function show(AddonTemplate $addonTemplate): JsonResponse
    {
        return response()->json([
            'data' => $addonTemplate,
            'message' => 'Addon template retrieved successfully'
        ]);
    }

    /**
     * Update the specified template (Admin only)
     */
    public function update(Request $request, AddonTemplate $addonTemplate): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyPermission(['admin.write', 'system.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to update addon templates'
            ], 403);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|string|in:product,service,license,hardware,software,expense,other',
            'sku' => 'nullable|string|max:100|unique:addon_templates,sku,' . $addonTemplate->id,
            'default_unit_price' => 'sometimes|numeric|min:0|max:999999.99',
            'default_quantity' => 'sometimes|numeric|min:0.01|max:99999.99',
            'allow_quantity_override' => 'boolean',
            'allow_price_override' => 'boolean',
            'is_billable' => 'boolean',
            'is_taxable' => 'boolean',
            'billing_category' => 'sometimes|string|in:addon,expense,product,service',
            'default_tax_rate' => 'nullable|numeric|min:0|max:1',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|array'
        ]);
        
        $addonTemplate->update($validated);
        
        return response()->json([
            'data' => $addonTemplate,
            'message' => 'Addon template updated successfully'
        ]);
    }

    /**
     * Remove the specified template (Admin only)
     */
    public function destroy(AddonTemplate $addonTemplate): JsonResponse
    {
        $user = request()->user();
        
        if (!$user->hasAnyPermission(['admin.write', 'system.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to delete addon templates'
            ], 403);
        }
        
        // Check if template is being used
        if ($addonTemplate->ticketAddons()->exists()) {
            return response()->json([
                'message' => 'Cannot delete template that is being used by existing addons'
            ], 422);
        }
        
        $addonTemplate->delete();
        
        return response()->json([
            'message' => 'Addon template deleted successfully'
        ]);
    }
    
    /**
     * Create addon from template for a specific ticket
     */
    public function createAddon(Request $request, AddonTemplate $addonTemplate): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'service_ticket_id' => 'required|exists:service_tickets,id',
            'quantity' => 'nullable|numeric|min:0.01|max:99999.99',
            'unit_price' => 'nullable|numeric|min:0|max:999999.99',
            'discount_amount' => 'nullable|numeric|min:0|max:999999.99',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array'
        ]);
        
        $ticket = ServiceTicket::findOrFail($validated['service_ticket_id']);
        $this->authorize('update', $ticket);
        
        // Prepare overrides
        $overrides = [];
        
        if (isset($validated['quantity']) && $addonTemplate->allow_quantity_override) {
            $overrides['quantity'] = $validated['quantity'];
        }
        
        if (isset($validated['unit_price']) && $addonTemplate->allow_price_override) {
            $overrides['unit_price'] = $validated['unit_price'];
        }
        
        if (isset($validated['discount_amount'])) {
            $overrides['discount_amount'] = $validated['discount_amount'];
        }
        
        if (isset($validated['description'])) {
            $overrides['description'] = $validated['description'];
        }
        
        if (isset($validated['metadata'])) {
            $overrides['metadata'] = $validated['metadata'];
        }
        
        $addon = $addonTemplate->createAddonForTicket($ticket, $user, $overrides);
        $addon->load(['addedBy:id,name', 'template:id,name']);
        
        return response()->json([
            'data' => $addon,
            'message' => 'Addon created from template successfully'
        ], 201);
    }
}
