<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketPriority;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class TicketPriorityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $priorities = TicketPriority::ordered()->get();
        
        return response()->json([
            'success' => true,
            'data' => $priorities
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:ticket_priorities,key|max:50',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'bg_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'severity_level' => 'required|integer|min:1|max:10',
            'escalation_multiplier' => 'required|numeric|min:0.1|max:10',
            'sort_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        // Auto-assign sort order if not provided
        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = TicketPriority::max('sort_order') + 1;
        }

        $priority = TicketPriority::create($validated);

        return response()->json([
            'success' => true,
            'data' => $priority,
            'message' => 'Priority created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TicketPriority $ticketPriority): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $ticketPriority
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TicketPriority $ticketPriority): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:50', Rule::unique('ticket_priorities')->ignore($ticketPriority->id)],
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'bg_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'severity_level' => 'required|integer|min:1|max:10',
            'escalation_multiplier' => 'required|numeric|min:0.1|max:10',
            'sort_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        $ticketPriority->update($validated);

        return response()->json([
            'success' => true,
            'data' => $ticketPriority->fresh(),
            'message' => 'Priority updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TicketPriority $ticketPriority): JsonResponse
    {
        try {
            $ticketPriority->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Priority deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reorder priorities
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'priorities' => 'required|array',
            'priorities.*.id' => 'required|string|exists:ticket_priorities,id',
            'priorities.*.sort_order' => 'required|integer|min:0'
        ]);

        foreach ($validated['priorities'] as $priorityData) {
            TicketPriority::where('id', $priorityData['id'])
                ->update(['sort_order' => $priorityData['sort_order']]);
        }

        $priorities = TicketPriority::ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $priorities,
            'message' => 'Priorities reordered successfully'
        ]);
    }

    /**
     * Get priority options for forms
     */
    public function options(): JsonResponse
    {
        $options = TicketPriority::getOptions();
        
        return response()->json([
            'success' => true,
            'data' => $options
        ]);
    }
}
