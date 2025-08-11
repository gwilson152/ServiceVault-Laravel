<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoleTemplate;
use App\Services\WidgetRegistryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class RoleTemplateController extends Controller
{
    public function __construct(
        private WidgetRegistryService $widgetService
    ) {}

    /**
     * Display a listing of role templates
     */
    public function index(Request $request): JsonResponse
    {
        $query = RoleTemplate::query()->with(['roles']);
        
        // Filter by context if provided
        if ($request->has('context')) {
            $query->where('context', $request->context);
        }
        
        // Filter by modifiable status
        if ($request->has('modifiable')) {
            $query->where('is_modifiable', $request->boolean('modifiable'));
        }
        
        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        $roleTemplates = $query->orderBy('name')->get();
        
        return response()->json([
            'data' => $roleTemplates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'context' => $template->context,
                    'is_system_role' => $template->is_system_role,
                    'is_default' => $template->is_default,
                    'is_modifiable' => $template->isModifiable(),
                    'users_count' => $template->roles->sum(function($role) {
                        return $role->users()->count();
                    }),
                    'permission_counts' => [
                        'functional' => count($template->getAllPermissions()),
                        'widget' => count($template->getAllWidgetPermissions()),
                        'page' => count($template->getAllPagePermissions()),
                    ],
                    'created_at' => $template->created_at,
                    'updated_at' => $template->updated_at,
                ];
            }),
        ]);
    }

    /**
     * Show the form for creating a new role template (API data)
     */
    public function create(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'available_permissions' => $this->getFunctionalPermissions(),
                'available_contexts' => ['service_provider', 'account_user', 'both'],
                'default_values' => [
                    'context' => 'service_provider',
                    'is_default' => false,
                    'is_modifiable' => true,
                    'permissions' => [],
                    'widget_permissions' => [],
                    'page_permissions' => [],
                ]
            ]
        ]);
    }

    /**
     * Preview widgets for a new role template being created
     */
    public function previewWidgetsForCreate(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Return empty/default widget preview for create scenario
        return response()->json([
            'data' => [
                'available_widgets' => $this->widgetService->getAvailableWidgets($user, 'service_provider'),
                'assigned_widgets' => [],
                'layout' => [
                    'columns' => 12,
                    'widgets' => []
                ]
            ]
        ]);
    }

    /**
     * Store a newly created role template
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:role_templates,name',
            'description' => 'nullable|string',
            'context' => ['required', Rule::in(['service_provider', 'account_user', 'both'])],
            'permissions' => 'array',
            'permissions.*' => 'string',
            'widget_permissions' => 'array',
            'widget_permissions.*' => 'string',
            'page_permissions' => 'array', 
            'page_permissions.*' => 'string',
            'is_default' => 'boolean',
        ]);
        
        // Validate permissions against available permissions
        $validated['permissions'] = $this->validateFunctionalPermissions($validated['permissions'] ?? []);
        $validated['widget_permissions'] = $this->widgetService->validateWidgetPermissions(
            $validated['widget_permissions'] ?? [], 
            $validated['context']
        );
        $validated['page_permissions'] = $this->validatePagePermissions($validated['page_permissions'] ?? []);
        
        $validated['is_system_role'] = false;
        $validated['is_modifiable'] = true;
        
        $roleTemplate = RoleTemplate::create($validated);
        
        return response()->json([
            'message' => 'Role template created successfully',
            'data' => $this->formatRoleTemplate($roleTemplate),
        ], 201);
    }

    /**
     * Display the specified role template
     */
    public function show(RoleTemplate $roleTemplate): JsonResponse
    {
        $roleTemplate->load(['roles.users', 'widgets']);
        
        return response()->json([
            'data' => $this->formatRoleTemplate($roleTemplate, true),
        ]);
    }

    /**
     * Update the specified role template
     */
    public function update(Request $request, RoleTemplate $roleTemplate): JsonResponse
    {
        // Check if role template is modifiable
        if (!$roleTemplate->isModifiable()) {
            return response()->json([
                'message' => 'This role template cannot be modified',
            ], 403);
        }
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('role_templates', 'name')->ignore($roleTemplate->id)],
            'description' => 'nullable|string',
            'context' => ['required', Rule::in(['service_provider', 'account_user', 'both'])],
            'permissions' => 'array',
            'permissions.*' => 'string',
            'widget_permissions' => 'array',
            'widget_permissions.*' => 'string',
            'page_permissions' => 'array',
            'page_permissions.*' => 'string',
            'is_default' => 'boolean',
        ]);
        
        // Validate permissions against available permissions
        $validated['permissions'] = $this->validateFunctionalPermissions($validated['permissions'] ?? []);
        $validated['widget_permissions'] = $this->widgetService->validateWidgetPermissions(
            $validated['widget_permissions'] ?? [], 
            $validated['context']
        );
        $validated['page_permissions'] = $this->validatePagePermissions($validated['page_permissions'] ?? []);
        
        $roleTemplate->update($validated);
        
        return response()->json([
            'message' => 'Role template updated successfully',
            'data' => $this->formatRoleTemplate($roleTemplate->fresh()),
        ]);
    }

    /**
     * Remove the specified role template
     */
    public function destroy(RoleTemplate $roleTemplate): JsonResponse
    {
        // Check if role template is modifiable
        if (!$roleTemplate->isModifiable()) {
            return response()->json([
                'message' => 'This role template cannot be deleted',
            ], 403);
        }
        
        // Check if role template is in use
        if ($roleTemplate->roles()->exists()) {
            return response()->json([
                'message' => 'Cannot delete role template that is currently in use',
            ], 422);
        }
        
        $roleTemplate->delete();
        
        return response()->json([
            'message' => 'Role template deleted successfully',
        ]);
    }
    
    /**
     * Get available permissions for role template creation/editing
     */
    public function permissions(): JsonResponse
    {
        $template = new RoleTemplate();
        
        return response()->json([
            'data' => [
                'functional_permissions' => $this->getFunctionalPermissions(),
                'widget_permissions' => $this->widgetService->getAllWidgetPermissionKeys(),
                'page_permissions' => $this->getPagePermissions(),
            ],
        ]);
    }
    
    /**
     * Preview widgets for role template
     */
    public function previewWidgets(RoleTemplate $roleTemplate): JsonResponse
    {
        $widgets = $this->widgetService->getWidgetsForRoleTemplate($roleTemplate);
        
        return response()->json([
            'data' => [
                'available_widgets' => $widgets,
                'widget_count' => count($widgets),
                'categories' => collect($widgets)->groupBy('category')->keys(),
            ],
        ]);
    }
    
    /**
     * Clone an existing role template
     */
    public function clone(RoleTemplate $roleTemplate, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:role_templates,name',
            'description' => 'nullable|string',
        ]);
        
        $cloned = RoleTemplate::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? "Copy of {$roleTemplate->name}",
            'context' => $roleTemplate->context,
            'permissions' => $roleTemplate->permissions,
            'widget_permissions' => $roleTemplate->widget_permissions,
            'page_permissions' => $roleTemplate->page_permissions,
            'is_system_role' => false,
            'is_default' => false,
            'is_modifiable' => true,
        ]);
        
        return response()->json([
            'message' => 'Role template cloned successfully',
            'data' => $this->formatRoleTemplate($cloned),
        ], 201);
    }

    /**
     * Update widget assignments and layout for role template
     */
    public function updateWidgets(RoleTemplate $roleTemplate, Request $request): JsonResponse
    {
        // Check if role template is modifiable
        if (!$roleTemplate->isModifiable()) {
            return response()->json([
                'message' => 'This role template cannot be modified',
            ], 403);
        }
        
        $validated = $request->validate([
            'widgets' => 'required|array',
            'widgets.*.widget_id' => 'required|string',
            'widgets.*.enabled' => 'boolean',
            'widgets.*.enabled_by_default' => 'boolean',
            'widgets.*.configurable' => 'boolean',
            'widgets.*.widget_config' => 'array',
            'layout' => 'array',
            'layout.*.i' => 'required|string',
            'layout.*.x' => 'required|integer|min:0|max:11',
            'layout.*.y' => 'required|integer|min:0',
            'layout.*.w' => 'required|integer|min:1|max:12',
            'layout.*.h' => 'required|integer|min:1|max:10',
            'layout.*.widget_config' => 'array',
        ]);
        
        try {
            \DB::beginTransaction();
            
            // Clear existing widget assignments
            $roleTemplate->widgets()->delete();
            
            // Create new widget assignments
            foreach ($validated['widgets'] as $index => $widgetData) {
                $roleTemplate->widgets()->create([
                    'widget_id' => $widgetData['widget_id'],
                    'enabled' => $widgetData['enabled'] ?? true,
                    'enabled_by_default' => $widgetData['enabled_by_default'] ?? false,
                    'configurable' => $widgetData['configurable'] ?? true,
                    'display_order' => $index + 1,
                    'widget_config' => $widgetData['widget_config'] ?? [],
                ]);
            }
            
            // Update widget permissions
            $widgetPermissions = collect($validated['widgets'])->pluck('widget_id')->map(function ($widgetId) {
                return "widgets.dashboard.{$widgetId}";
            })->toArray();
            
            // Merge with existing widget permissions (keep global ones like widgets.configure)
            $existingGlobalPermissions = array_filter($roleTemplate->getAllWidgetPermissions(), function ($permission) {
                return !str_starts_with($permission, 'widgets.dashboard.');
            });
            
            $roleTemplate->update([
                'widget_permissions' => array_merge($existingGlobalPermissions, $widgetPermissions),
                'dashboard_layout' => $validated['layout'] ?? [],
            ]);
            
            \DB::commit();
            
            return response()->json([
                'message' => 'Widget assignments updated successfully',
                'data' => [
                    'widgets_count' => count($validated['widgets']),
                    'layout_items' => count($validated['layout'] ?? []),
                ],
            ]);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to update widget assignments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current widget assignments for role template
     */
    public function getWidgets(RoleTemplate $roleTemplate): JsonResponse
    {
        $widgets = $roleTemplate->widgets()->orderBy('display_order')->get();
        $layout = $roleTemplate->dashboard_layout ?? [];
        
        return response()->json([
            'data' => [
                'widgets' => $widgets->map(function ($widget) {
                    return [
                        'id' => $widget->widget_id,
                        'widget_id' => $widget->widget_id,
                        'enabled' => $widget->enabled,
                        'enabled_by_default' => $widget->enabled_by_default,
                        'configurable' => $widget->configurable,
                        'display_order' => $widget->display_order,
                        'widget_config' => $widget->widget_config,
                    ];
                }),
                'layout' => $layout,
            ],
        ]);
    }

    /**
     * Format role template for API response
     */
    private function formatRoleTemplate(RoleTemplate $roleTemplate, bool $includeDetails = false): array
    {
        $data = [
            'id' => $roleTemplate->id,
            'name' => $roleTemplate->name,
            'description' => $roleTemplate->description,
            'context' => $roleTemplate->context,
            'is_system_role' => $roleTemplate->is_system_role,
            'is_default' => $roleTemplate->is_default,
            'is_modifiable' => $roleTemplate->isModifiable(),
            'permissions' => $roleTemplate->getAllPermissions(),
            'widget_permissions' => $roleTemplate->getAllWidgetPermissions(),
            'page_permissions' => $roleTemplate->getAllPagePermissions(),
            'created_at' => $roleTemplate->created_at,
            'updated_at' => $roleTemplate->updated_at,
        ];
        
        if ($includeDetails) {
            $data['users_count'] = $roleTemplate->roles->sum(function($role) {
                return $role->users()->count();
            });
            $data['roles'] = $roleTemplate->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'users_count' => $role->users()->count(),
                ];
            });
            $data['widget_configurations'] = $roleTemplate->widgets->map(function ($widget) {
                return [
                    'widget_id' => $widget->widget_id,
                    'enabled' => $widget->enabled,
                    'display_order' => $widget->display_order,
                    'config' => $widget->getConfigWithDefaults(),
                ];
            });
        }
        
        return $data;
    }

    /**
     * Validate functional permissions against available permissions
     */
    private function validateFunctionalPermissions(array $permissions): array
    {
        $template = new RoleTemplate();
        $availablePermissions = $template->getAllPossiblePermissions();
        
        return array_values(array_intersect($permissions, $availablePermissions));
    }
    
    /**
     * Validate page permissions
     */
    private function validatePagePermissions(array $permissions): array
    {
        $template = new RoleTemplate();
        $availablePermissions = $template->getAllPossiblePagePermissions();
        
        return array_values(array_intersect($permissions, $availablePermissions));
    }
    
    /**
     * Get formatted functional permissions for UI
     */
    private function getFunctionalPermissions(): array
    {
        $template = new RoleTemplate();
        $permissions = $template->getAllPossiblePermissions();
        
        return collect($permissions)->map(function ($permission) {
            $parts = explode('.', $permission);
            $category = $parts[0] ?? 'general';
            $action = $parts[1] ?? 'access';
            $scope = $parts[2] ?? null;
            
            return [
                'key' => $permission,
                'name' => ucwords(str_replace(['.', '_'], [' ', ' '], $permission)),
                'description' => $this->generatePermissionDescription($permission),
                'category' => $category,
                'action' => $action,
                'scope' => $scope,
            ];
        })->groupBy('category')->toArray();
    }
    
    /**
     * Get formatted page permissions for UI
     */
    private function getPagePermissions(): array
    {
        $template = new RoleTemplate();
        $permissions = $template->getAllPossiblePagePermissions();
        
        return collect($permissions)->map(function ($permission) {
            $parts = explode('.', $permission);
            $category = $parts[1] ?? 'general';
            $page = $parts[2] ?? 'access';
            
            return [
                'key' => $permission,
                'name' => ucwords(str_replace(['.', '_'], [' ', ' '], $permission)),
                'description' => $this->generatePermissionDescription($permission),
                'category' => $category,
                'page' => $page,
            ];
        })->groupBy('category')->toArray();
    }
    
    /**
     * Generate description for permission
     */
    private function generatePermissionDescription(string $permission): string
    {
        $descriptions = [
            'admin.manage' => 'Full administrative access to system management',
            'system.configure' => 'Configure system-wide settings and preferences',
            'accounts.create' => 'Create new customer accounts',
            'accounts.manage' => 'Manage existing customer accounts',
            'tickets.view.all' => 'View all service tickets across all accounts',
            'tickets.view.account' => 'View tickets for accounts user has access to',
            'tickets.create' => 'Create new service tickets',
            'time.track' => 'Track time on service tickets',
            'time.approve' => 'Approve time entries submitted by team members',
            'billing.view.account' => 'View billing information for accessible accounts',
            'portal.access' => 'Access customer portal interface',
        ];
        
        return $descriptions[$permission] ?? ucwords(str_replace(['.', '_'], [' ', ' '], $permission));
    }
}
