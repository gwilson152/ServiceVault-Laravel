<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoleTemplate;
use App\Models\WidgetPermission;
use App\Services\WidgetRegistryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WidgetPermissionController extends Controller
{
    public function __construct(
        private WidgetRegistryService $widgetService
    ) {}

    /**
     * Display a listing of widget permissions
     */
    public function index(Request $request): JsonResponse
    {
        $query = WidgetPermission::query();

        // Filter by context
        if ($request->has('context')) {
            $query->forContext($request->context);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Search by name or widget_id
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('widget_name', 'like', "%{$search}%")
                    ->orWhere('widget_id', 'like', "%{$search}%");
            });
        }

        $widgetPermissions = $query->orderBy('category')->orderBy('widget_name')->get();

        return response()->json([
            'data' => $widgetPermissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'widget_id' => $permission->widget_id,
                    'widget_name' => $permission->getDisplayName(),
                    'permission_key' => $permission->permission_key,
                    'description' => $permission->description,
                    'category' => $permission->category,
                    'context' => $permission->context,
                    'required_permissions' => $permission->required_permissions,
                    'is_configurable' => $permission->is_configurable,
                    'default_enabled' => $permission->default_enabled,
                    'role_templates_count' => $permission->roleTemplates()->count(),
                ];
            }),
        ]);
    }

    /**
     * Store a newly created widget permission
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widget_id' => 'required|string|max:255|unique:widget_permissions,widget_id',
            'widget_name' => 'required|string|max:255',
            'permission_key' => 'required|string|max:255|unique:widget_permissions,permission_key',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'context' => ['required', Rule::in(['service_provider', 'account_user', 'both'])],
            'required_permissions' => 'array',
            'required_permissions.*' => 'string',
            'is_configurable' => 'boolean',
            'default_enabled' => 'boolean',
        ]);

        $validated['required_permissions'] = $validated['required_permissions'] ?? [];
        $validated['is_configurable'] = $validated['is_configurable'] ?? true;
        $validated['default_enabled'] = $validated['default_enabled'] ?? false;

        $widgetPermission = WidgetPermission::create($validated);

        return response()->json([
            'message' => 'Widget permission created successfully',
            'data' => $widgetPermission,
        ], 201);
    }

    /**
     * Display the specified widget permission
     */
    public function show(WidgetPermission $widgetPermission): JsonResponse
    {
        $widgetPermission->load(['roleTemplates', 'configurations']);

        return response()->json([
            'data' => [
                'id' => $widgetPermission->id,
                'widget_id' => $widgetPermission->widget_id,
                'widget_name' => $widgetPermission->getDisplayName(),
                'permission_key' => $widgetPermission->permission_key,
                'description' => $widgetPermission->description,
                'category' => $widgetPermission->category,
                'context' => $widgetPermission->context,
                'required_permissions' => $widgetPermission->required_permissions,
                'is_configurable' => $widgetPermission->is_configurable,
                'default_enabled' => $widgetPermission->default_enabled,
                'role_templates' => $widgetPermission->roleTemplates->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'context' => $template->context,
                    ];
                }),
                'configurations' => $widgetPermission->configurations->map(function ($config) {
                    return [
                        'role_template_id' => $config->role_template_id,
                        'role_template_name' => $config->roleTemplate->name,
                        'enabled' => $config->enabled,
                        'display_order' => $config->display_order,
                        'config' => $config->getConfigWithDefaults(),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update the specified widget permission
     */
    public function update(Request $request, WidgetPermission $widgetPermission): JsonResponse
    {
        $validated = $request->validate([
            'widget_id' => ['required', 'string', 'max:255', Rule::unique('widget_permissions', 'widget_id')->ignore($widgetPermission->id)],
            'widget_name' => 'required|string|max:255',
            'permission_key' => ['required', 'string', 'max:255', Rule::unique('widget_permissions', 'permission_key')->ignore($widgetPermission->id)],
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'context' => ['required', Rule::in(['service_provider', 'account_user', 'both'])],
            'required_permissions' => 'array',
            'required_permissions.*' => 'string',
            'is_configurable' => 'boolean',
            'default_enabled' => 'boolean',
        ]);

        $widgetPermission->update($validated);

        return response()->json([
            'message' => 'Widget permission updated successfully',
            'data' => $widgetPermission->fresh(),
        ]);
    }

    /**
     * Remove the specified widget permission
     */
    public function destroy(WidgetPermission $widgetPermission): JsonResponse
    {
        // Check if widget permission is in use by role templates
        if ($widgetPermission->roleTemplates()->exists()) {
            return response()->json([
                'message' => 'Cannot delete widget permission that is currently assigned to role templates',
            ], 422);
        }

        $widgetPermission->delete();

        return response()->json([
            'message' => 'Widget permission deleted successfully',
        ]);
    }

    /**
     * Sync widget permissions from WidgetRegistryService
     */
    public function sync(): JsonResponse
    {
        $widgetPermissionKeys = $this->widgetService->getAllWidgetPermissionKeys();
        $synced = 0;
        $updated = 0;

        foreach ($widgetPermissionKeys as $widgetData) {
            $widgetPermission = WidgetPermission::updateOrCreate(
                ['permission_key' => $widgetData['key']],
                [
                    'widget_id' => str_replace('widgets.dashboard.', '', $widgetData['key']),
                    'widget_name' => $widgetData['name'],
                    'description' => $widgetData['description'],
                    'category' => $widgetData['category'],
                    'context' => $widgetData['context'],
                    'required_permissions' => [],
                    'is_configurable' => true,
                    'default_enabled' => false,
                ]
            );

            if ($widgetPermission->wasRecentlyCreated) {
                $synced++;
            } else {
                $updated++;
            }
        }

        return response()->json([
            'message' => 'Widget permissions synced successfully',
            'data' => [
                'synced' => $synced,
                'updated' => $updated,
                'total' => $synced + $updated,
            ],
        ]);
    }

    /**
     * Get widget categories
     */
    public function categories(): JsonResponse
    {
        $categories = WidgetPermission::select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Assign widget permission to role template
     */
    public function assignToRole(WidgetPermission $widgetPermission, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_template_id' => 'required|exists:role_templates,id',
            'enabled' => 'boolean',
            'display_order' => 'integer|min:0',
            'widget_config' => 'array',
        ]);

        $roleTemplate = RoleTemplate::findOrFail($validated['role_template_id']);

        // Check if role template is modifiable
        if (! $roleTemplate->isModifiable()) {
            return response()->json([
                'message' => 'This role template cannot be modified',
            ], 403);
        }

        // Check context compatibility
        if (! $widgetPermission->isAvailableInContext($roleTemplate->context) && $roleTemplate->context !== 'both') {
            return response()->json([
                'message' => 'Widget permission is not compatible with role template context',
            ], 422);
        }

        $roleTemplate->widgets()->updateOrCreate(
            ['widget_id' => $widgetPermission->widget_id],
            [
                'enabled' => $validated['enabled'] ?? true,
                'display_order' => $validated['display_order'] ?? 0,
                'widget_config' => $validated['widget_config'] ?? [],
            ]
        );

        return response()->json([
            'message' => 'Widget permission assigned to role template successfully',
        ]);
    }

    /**
     * Remove widget permission from role template
     */
    public function removeFromRole(WidgetPermission $widgetPermission, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_template_id' => 'required|exists:role_templates,id',
        ]);

        $roleTemplate = RoleTemplate::findOrFail($validated['role_template_id']);

        // Check if role template is modifiable
        if (! $roleTemplate->isModifiable()) {
            return response()->json([
                'message' => 'This role template cannot be modified',
            ], 403);
        }

        $roleTemplate->widgets()
            ->where('widget_id', $widgetPermission->widget_id)
            ->delete();

        return response()->json([
            'message' => 'Widget permission removed from role template successfully',
        ]);
    }
}
