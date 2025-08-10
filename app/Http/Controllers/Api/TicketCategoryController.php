<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketCategoryController extends Controller
{
    /**
     * Display a listing of ticket categories
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Only admins can view all categories
        if (!$user->hasAnyPermission(['admin.read', 'settings.manage', 'tickets.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to view ticket categories'
            ], 403);
        }

        $query = TicketCategory::query();

        // Filter by active status
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Filter by approval requirement
        if ($request->filled('requires_approval')) {
            $query->where('requires_approval', $request->boolean('requires_approval'));
        }

        $categories = $query->ordered()->get();

        return response()->json([
            'data' => $categories,
            'statistics' => TicketCategory::getStatistics(),
            'message' => 'Ticket categories retrieved successfully'
        ]);
    }

    /**
     * Get category options for forms
     */
    public function options(Request $request): JsonResponse
    {
        $options = TicketCategory::getOptions();
        
        return response()->json([
            'options' => $options,
            'default_category' => TicketCategory::getDefault()?->key
        ]);
    }

    /**
     * Store a newly created ticket category (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyPermission(['admin.write', 'settings.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to create ticket categories'
            ], 403);
        }

        $validated = $request->validate([
            'key' => 'required|string|max:50|unique:ticket_categories,key|alpha_dash',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'bg_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'required|string|max:50',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'requires_approval' => 'boolean',
            'default_priority_multiplier' => 'numeric|min:0|max:10',
            'default_estimated_hours' => 'nullable|integer|min:0',
            'sla_hours' => 'nullable|integer|min:1',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        // Set defaults
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['is_default'] = $validated['is_default'] ?? false;
        $validated['requires_approval'] = $validated['requires_approval'] ?? false;
        $validated['default_priority_multiplier'] = $validated['default_priority_multiplier'] ?? 1.00;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $category = TicketCategory::create($validated);

        return response()->json([
            'data' => $category,
            'message' => 'Ticket category created successfully'
        ], 201);
    }

    /**
     * Display the specified ticket category
     */
    public function show(TicketCategory $ticketCategory): JsonResponse
    {
        $category = $ticketCategory->load('serviceTickets');
        
        // Get category statistics
        $ticketCount = $category->serviceTickets()->count();
        $openTicketCount = $category->serviceTickets()
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->count();
        
        $recentTickets = $category->serviceTickets()
            ->with(['assignedUsers', 'account'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'data' => $category,
            'statistics' => [
                'total_tickets' => $ticketCount,
                'open_tickets' => $openTicketCount,
                'recent_tickets' => $recentTickets,
                'suggested_priority' => $category->getSuggestedPriority('medium'),
                'formatted_sla' => $category->formatted_sla
            ],
            'message' => 'Ticket category retrieved successfully'
        ]);
    }

    /**
     * Update the specified ticket category (Admin only)
     */
    public function update(Request $request, TicketCategory $ticketCategory): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->hasAnyPermission(['admin.write', 'settings.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to update ticket categories'
            ], 403);
        }

        $validated = $request->validate([
            'key' => [
                'sometimes',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('ticket_categories', 'key')->ignore($ticketCategory->id)
            ],
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'bg_color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon' => 'sometimes|string|max:50',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'requires_approval' => 'boolean',
            'default_priority_multiplier' => 'numeric|min:0|max:10',
            'default_estimated_hours' => 'nullable|integer|min:0',
            'sla_hours' => 'nullable|integer|min:1',
            'sort_order' => 'integer|min:0',
            'metadata' => 'nullable|array'
        ]);

        $ticketCategory->update($validated);

        return response()->json([
            'data' => $ticketCategory,
            'message' => 'Ticket category updated successfully'
        ]);
    }

    /**
     * Remove the specified ticket category (Admin only)
     */
    public function destroy(TicketCategory $ticketCategory): JsonResponse
    {
        $user = request()->user();
        
        if (!$user->hasAnyPermission(['admin.write', 'settings.manage'])) {
            return response()->json([
                'message' => 'Insufficient permissions to delete ticket categories'
            ], 403);
        }

        // Check if category is being used
        if ($ticketCategory->serviceTickets()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category that is being used by existing tickets'
            ], 422);
        }

        // Prevent deletion of default category
        if ($ticketCategory->is_default) {
            return response()->json([
                'message' => 'Cannot delete the default ticket category'
            ], 422);
        }

        $ticketCategory->delete();

        return response()->json([
            'message' => 'Ticket category deleted successfully'
        ]);
    }

    /**
     * Get category statistics for dashboard/reporting
     */
    public function statistics(): JsonResponse
    {
        $user = request()->user();
        
        if (!$user->hasAnyPermission(['admin.read', 'tickets.view', 'reports.view'])) {
            return response()->json([
                'message' => 'Insufficient permissions to view category statistics'
            ], 403);
        }

        $statistics = TicketCategory::getStatistics();
        
        return response()->json([
            'data' => $statistics,
            'message' => 'Category statistics retrieved successfully'
        ]);
    }

    /**
     * Validate SLA breach status for categories
     */
    public function slaStatus(): JsonResponse
    {
        $user = request()->user();
        
        if (!$user->hasAnyPermission(['admin.read', 'tickets.view', 'reports.view'])) {
            return response()->json([
                'message' => 'Insufficient permissions to view SLA status'
            ], 403);
        }

        $categories = TicketCategory::active()
            ->whereNotNull('sla_hours')
            ->with(['serviceTickets' => function ($query) {
                $query->whereNotIn('status', ['closed', 'cancelled'])
                    ->where('created_at', '>=', now()->subDays(30));
            }])
            ->get();

        $slaStatus = $categories->map(function ($category) {
            $tickets = $category->serviceTickets;
            $breachedTickets = $tickets->filter(function ($ticket) use ($category) {
                return $category->isSlaBreached($ticket->created_at);
            });

            return [
                'category' => [
                    'key' => $category->key,
                    'name' => $category->name,
                    'sla_hours' => $category->sla_hours,
                    'formatted_sla' => $category->formatted_sla
                ],
                'total_tickets' => $tickets->count(),
                'breached_tickets' => $breachedTickets->count(),
                'breach_percentage' => $tickets->count() > 0 
                    ? round(($breachedTickets->count() / $tickets->count()) * 100, 1) 
                    : 0,
                'upcoming_breaches' => $tickets->filter(function ($ticket) use ($category) {
                    $deadline = $category->getSlaDeadline($ticket->created_at);
                    return $deadline && $deadline->diffInHours(now()) <= 4;
                })->count()
            ];
        });

        return response()->json([
            'data' => $slaStatus,
            'message' => 'SLA status retrieved successfully'
        ]);
    }
}