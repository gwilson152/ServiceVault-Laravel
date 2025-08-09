<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class WidgetRegistryService
{
    protected array $discoveredWidgets = [];
    protected bool $isDiscovered = false;
    
    protected const WIDGET_DIRECTORIES = [
        'resources/js/Components/Widgets',
        'resources/js/Components/Dashboard/Widgets',
    ];
    
    protected const CACHE_KEY = 'widget_registry_discovered';
    protected const CACHE_TTL = 3600; // 1 hour in production, disabled in development
    /**
     * Registry of all available widgets with their configurations
     */
    private const WIDGET_REGISTRY = [
        'system-health' => [
            'name' => 'System Health',
            'description' => 'Monitor system status, database, Redis, and queue health',
            'component' => 'SystemHealthWidget',
            'category' => 'administration',
            'permissions' => ['system.manage'],
            'context' => 'service_provider',
            'default_size' => ['w' => 4, 'h' => 2],
            'configurable' => true,
            'enabled_by_default' => true,
        ],
        
        'system-stats' => [
            'name' => 'System Statistics', 
            'description' => 'Overview of users, accounts, timers, and system activity',
            'component' => 'SystemStatsWidget',
            'category' => 'administration',
            'permissions' => ['admin.manage', 'system.manage'],
            'context' => 'service_provider',
            'default_size' => ['w' => 8, 'h' => 2],
            'configurable' => false,
            'enabled_by_default' => true,
        ],

        'user-management' => [
            'name' => 'User Management',
            'description' => 'Quick access to user administration functions',
            'component' => 'UserManagementWidget',
            'category' => 'administration',
            'permissions' => ['users.manage'],
            'context' => 'service_provider',
            'default_size' => ['w' => 4, 'h' => 3],
            'configurable' => true,
            'enabled_by_default' => true,
        ],

        'account-management' => [
            'name' => 'Account Management',
            'description' => 'Manage customer accounts and organizational structure',
            'component' => 'AccountManagementWidget',
            'category' => 'administration',
            'permissions' => ['accounts.manage', 'accounts.create'],
            'context' => 'service_provider',
            'default_size' => ['w' => 4, 'h' => 3],
            'configurable' => true,
            'enabled_by_default' => true,
        ],

        'ticket-overview' => [
            'name' => 'Service Tickets Overview',
            'description' => 'Overview of service tickets across accounts or for specific account',
            'component' => 'TicketOverviewWidget',
            'category' => 'service_delivery',
            'permissions' => ['tickets.view.all', 'tickets.view.account', 'tickets.view.assigned'],
            'context' => 'both',
            'account_aware' => true,
            'default_size' => ['w' => 6, 'h' => 4],
            'configurable' => true,
            'enabled_by_default' => true,
        ],

        'my-tickets' => [
            'name' => 'My Tickets',
            'description' => 'Tickets assigned to the current user',
            'component' => 'MyTicketsWidget',
            'category' => 'service_delivery',
            'permissions' => ['tickets.view.assigned', 'tickets.view.own'],
            'context' => 'both',
            'account_aware' => true,
            'default_size' => ['w' => 6, 'h' => 4],
            'configurable' => true,
            'enabled_by_default' => true,
        ],

        'time-tracking' => [
            'name' => 'Active Timers',
            'description' => 'View and manage active time tracking sessions',
            'component' => 'TimeTrackingWidget',
            'category' => 'time_management',
            'permissions' => ['time.track', 'timers.create'],
            'context' => 'service_provider',
            'default_size' => ['w' => 4, 'h' => 3],
            'configurable' => true,
            'enabled_by_default' => true,
        ],

        'all-timers' => [
            'name' => 'All Active Timers',
            'description' => 'Monitor all active timers across all users (Admin only)',
            'component' => 'AllTimersWidget',
            'category' => 'administration',
            'permissions' => ['admin.read', 'super_admin'],
            'context' => 'service_provider',
            'default_size' => ['w' => 6, 'h' => 4],
            'configurable' => true,
            'enabled_by_default' => true,
        ],

        'time-entries' => [
            'name' => 'Recent Time Entries',
            'description' => 'View recent time entries and approve/manage them',
            'component' => 'TimeEntriesWidget',
            'category' => 'time_management', 
            'permissions' => ['time.view.own', 'time.view.all'],
            'context' => 'service_provider',
            'account_aware' => true,
            'default_size' => ['w' => 6, 'h' => 4],
            'configurable' => true,
            'enabled_by_default' => true,
        ],

        'billing-overview' => [
            'name' => 'Billing Overview',
            'description' => 'Financial overview and billing information',
            'component' => 'BillingOverviewWidget',
            'category' => 'financial',
            'permissions' => ['billing.view.account', 'billing.manage', 'billing.reports'],
            'context' => 'both',
            'account_aware' => true,
            'default_size' => ['w' => 4, 'h' => 3],
            'configurable' => true,
            'enabled_by_default' => false,
        ],


        'account-activity' => [
            'name' => 'Account Activity',
            'description' => 'Recent activity and communications for account',
            'component' => 'AccountActivityWidget',
            'category' => 'communication',
            'permissions' => ['tickets.view.account', 'accounts.view'],
            'context' => 'both',
            'account_aware' => true,
            'default_size' => ['w' => 6, 'h' => 4],
            'configurable' => true,
            'enabled_by_default' => true,
        ],

        'account-users' => [
            'name' => 'Account Users',
            'description' => 'Manage users within the customer account',
            'component' => 'AccountUsersWidget', 
            'category' => 'administration',
            'permissions' => ['users.assign', 'account.admin'],
            'context' => 'account_user',
            'account_aware' => true,
            'default_size' => ['w' => 4, 'h' => 3],
            'configurable' => true,
            'enabled_by_default' => true,
        ],

        'quick-actions' => [
            'name' => 'Quick Actions',
            'description' => 'Common actions and shortcuts based on user role',
            'component' => 'QuickActionsWidget',
            'category' => 'productivity',
            'permissions' => [], // Always available
            'context' => 'both',
            'default_size' => ['w' => 4, 'h' => 2],
            'configurable' => true,
            'enabled_by_default' => true,
        ],
    ];

    /**
     * Get all available widgets for a user based on their permissions
     */
    public function getAvailableWidgets(User $user, ?string $context = null): array
    {
        $userContext = $this->determineUserContext($user);
        $allWidgets = $this->getAllWidgets();
        
        return collect($allWidgets)
            ->filter(function ($widget) use ($user, $userContext) {
                // Filter by context (service_provider, account_user, both)
                if ($widget['context'] !== 'both' && $widget['context'] !== $userContext) {
                    return false;
                }
                
                // Filter by permissions
                if (!empty($widget['permissions'])) {
                    $permissions = is_array($widget['permissions']) ? $widget['permissions'] : [$widget['permissions']];
                    return $user->hasAnyPermission($permissions);
                }
                
                return true;
            })
            ->map(function ($widget, $key) {
                return array_merge($widget, ['id' => $key]);
            })
            ->values()
            ->toArray();
    }

    /**
     * Get widgets filtered by category
     */
    public function getWidgetsByCategory(User $user, string $category): array
    {
        return collect($this->getAvailableWidgets($user))
            ->filter(fn($widget) => $widget['category'] === $category)
            ->values()
            ->toArray();
    }

    /**
     * Get widget configuration by ID
     */
    public function getWidget(string $widgetId): ?array
    {
        $allWidgets = $this->getAllWidgets();
        return $allWidgets[$widgetId] ?? null;
    }

    /**
     * Get all widget categories
     */
    public function getCategories(): array
    {
        $allWidgets = $this->getAllWidgets();
        return collect($allWidgets)
            ->pluck('category')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Check if user has permission for specific widget
     */
    public function userCanAccessWidget(User $user, string $widgetId): bool
    {
        $widget = $this->getWidget($widgetId);
        
        if (!$widget) {
            return false;
        }

        // Check context
        $userContext = $this->determineUserContext($user);
        if ($widget['context'] !== 'both' && $widget['context'] !== $userContext) {
            return false;
        }

        // Check permissions
        if (!empty($widget['permissions'])) {
            return $user->hasAnyPermission($widget['permissions']);
        }

        return true;
    }

    /**
     * Get default widget layout for a user based on their role templates
     */
    public function getDefaultLayout(User $user): array
    {
        $widgets = $this->getAvailableWidgets($user);
        $layout = [];
        $currentRow = 0;
        $currentCol = 0;
        $maxCols = 12; // Grid system with 12 columns

        foreach ($widgets as $widget) {
            if (!$widget['enabled_by_default']) {
                continue;
            }

            // Safely extract widget size with defaults
            $defaultSize = $widget['default_size'] ?? ['w' => 4, 'h' => 3];
            if (is_string($defaultSize)) {
                // If parsing failed and it's still a string, use defaults
                $defaultSize = ['w' => 4, 'h' => 3];
            }
            
            $widgetWidth = $defaultSize['w'] ?? 4;
            $widgetHeight = $defaultSize['h'] ?? 3;

            // Check if widget fits in current row
            if ($currentCol + $widgetWidth > $maxCols) {
                $currentRow += 1;
                $currentCol = 0;
            }

            $layout[] = [
                'i' => $widget['id'],
                'x' => $currentCol,
                'y' => $currentRow,
                'w' => $widgetWidth,
                'h' => $widgetHeight,
            ];

            $currentCol += $widgetWidth;
        }

        return $layout;
    }

    /**
     * Determine user context (service_provider or account_user)
     */
    private function determineUserContext(User $user): string
    {
        // Check if user belongs to the service provider company
        // This could be determined by checking if they have service provider permissions
        // or by checking their account relationship
        
        if ($user->hasAnyPermission(['admin.manage', 'system.manage', 'users.manage'])) {
            return 'service_provider';
        }
        
        // Check if they have service delivery permissions
        if ($user->hasAnyPermission(['time.track', 'tickets.assign'])) {
            return 'service_provider';  
        }
        
        // Default to account user if they only have account-level permissions
        return 'account_user';
    }

    /**
     * Get all widgets (static + discovered)
     */
    protected function getAllWidgets(): array
    {
        if (!$this->isDiscovered) {
            $this->discoverWidgets();
        }
        
        // Merge static and discovered widgets, with discovered taking precedence
        return array_merge(self::WIDGET_REGISTRY, $this->discoveredWidgets);
    }
    
    /**
     * Discover widgets from filesystem
     */
    protected function discoverWidgets(): void
    {
        // Use cache in production, skip in development for hot reloading
        if (config('app.env') === 'production') {
            $cached = Cache::get(self::CACHE_KEY);
            if ($cached !== null) {
                $this->discoveredWidgets = $cached;
                $this->isDiscovered = true;
                return;
            }
        }
        
        $discovered = [];
        
        foreach (self::WIDGET_DIRECTORIES as $directory) {
            $fullPath = base_path($directory);
            
            if (!File::exists($fullPath)) {
                continue;
            }
            
            $files = File::allFiles($fullPath);
            
            foreach ($files as $file) {
                if ($file->getExtension() === 'vue' && str_ends_with($file->getFilename(), 'Widget.vue')) {
                    $widgetConfig = $this->extractWidgetConfig($file->getPathname());
                    
                    if ($widgetConfig) {
                        $discovered[$widgetConfig['id']] = $widgetConfig;
                    }
                }
            }
        }
        
        $this->discoveredWidgets = $discovered;
        $this->isDiscovered = true;
        
        // Cache in production
        if (config('app.env') === 'production') {
            Cache::put(self::CACHE_KEY, $discovered, self::CACHE_TTL);
        }
    }
    
    /**
     * Extract widget configuration from Vue component file
     */
    protected function extractWidgetConfig(string $filePath): ?array
    {
        $content = File::get($filePath);
        $relativePath = str_replace(base_path('resources/js/Components/'), '', $filePath);
        $componentName = pathinfo($filePath, PATHINFO_FILENAME);
        
        // Extract widget configuration from the Vue file
        // Look for widgetConfig export or embedded configuration
        if (preg_match('/export\s+const\s+widgetConfig\s*=\s*({[\s\S]*?});?(?=\n|$)/m', $content, $matches)) {
            try {
                $configString = $matches[1];
                // Convert JavaScript object to PHP array (simplified parser)
                $config = $this->parseJavaScriptObject($configString);
                
                // Generate ID from filename
                $widgetId = $this->generateWidgetId($componentName);
                
                // Set defaults for discovered widgets
                $defaultConfig = [
                    'id' => $widgetId,
                    'component' => $componentName,
                    'discovered' => true,
                    'file_path' => $relativePath,
                    'configurable' => true,
                    'enabled_by_default' => false,
                    'default_size' => ['w' => 4, 'h' => 3],
                    'context' => 'both',
                    'permissions' => [],
                    'category' => 'general',
                ];
                
                return array_merge($defaultConfig, $config);
                
            } catch (\Exception $e) {
                Log::warning("Failed to parse widget config from {$filePath}: {$e->getMessage()}");
                return null;
            }
        }
        
        // If no explicit config found, create minimal default
        return [
            'id' => $this->generateWidgetId($componentName),
            'name' => $this->humanizeWidgetName($componentName),
            'description' => "Auto-discovered widget: {$componentName}",
            'component' => $componentName,
            'category' => 'general',
            'permissions' => [],
            'context' => 'both',
            'default_size' => ['w' => 4, 'h' => 3],
            'configurable' => true,
            'enabled_by_default' => false,
            'discovered' => true,
            'file_path' => $relativePath,
        ];
    }
    
    /**
     * Parse JavaScript object string to PHP array (simplified)
     */
    protected function parseJavaScriptObject(string $jsObject): array
    {
        // This is a simplified parser - for production, consider using a proper JS parser
        $jsObject = trim($jsObject, '{}');
        $result = [];
        
        // Enhanced regex to handle nested objects
        if (preg_match_all('/(\w+)\s*:\s*(\{[^}]*\}|\[[^\]]*\]|[^,}]+)(?:,|$)/m', $jsObject, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = trim($match[1]);
                $value = trim($match[2]);
                
                // Parse different value types
                if ($value === 'true') {
                    $result[$key] = true;
                } elseif ($value === 'false') {
                    $result[$key] = false;
                } elseif (is_numeric($value)) {
                    $result[$key] = (int) $value;
                } elseif (preg_match('/^["\'](.+)["\']$/', $value, $stringMatch)) {
                    $result[$key] = $stringMatch[1];
                } elseif (preg_match('/^\{([^}]+)\}$/', $value, $objectMatch)) {
                    // Parse nested object like { w: 4, h: 2 }
                    $nestedResult = [];
                    if (preg_match_all('/(\w+)\s*:\s*([^,}]+)(?:,|$)/', $objectMatch[1], $nestedMatches, PREG_SET_ORDER)) {
                        foreach ($nestedMatches as $nestedMatch) {
                            $nestedKey = trim($nestedMatch[1]);
                            $nestedValue = trim($nestedMatch[2]);
                            if (is_numeric($nestedValue)) {
                                $nestedResult[$nestedKey] = (int) $nestedValue;
                            } elseif (preg_match('/^["\'](.+)["\']$/', $nestedValue, $nestedStringMatch)) {
                                $nestedResult[$nestedKey] = $nestedStringMatch[1];
                            } else {
                                $nestedResult[$nestedKey] = $nestedValue;
                            }
                        }
                    }
                    $result[$key] = $nestedResult;
                } elseif (preg_match('/^\[([^\]]+)\]$/', $value, $arrayMatch)) {
                    $result[$key] = array_map('trim', explode(',', $arrayMatch[1]));
                    $result[$key] = array_map(function($item) {
                        return trim($item, '"\'\'');
                    }, $result[$key]);
                } else {
                    $result[$key] = $value;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Generate widget ID from component name
     */
    protected function generateWidgetId(string $componentName): string
    {
        // Convert SystemHealthWidget -> system-health
        $id = preg_replace('/Widget$/', '', $componentName);
        $id = preg_replace('/([a-z])([A-Z])/', '$1-$2', $id);
        return strtolower($id);
    }
    
    /**
     * Humanize widget name from component name
     */
    protected function humanizeWidgetName(string $componentName): string
    {
        $name = preg_replace('/Widget$/', '', $componentName);
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);
        return $name;
    }
    
    /**
     * Clear discovered widget cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->discoveredWidgets = [];
        $this->isDiscovered = false;
    }
    
    /**
     * Get widget discovery statistics
     */
    public function getDiscoveryStats(): array
    {
        if (!$this->isDiscovered) {
            $this->discoverWidgets();
        }
        
        return [
            'static_widgets' => count(self::WIDGET_REGISTRY),
            'discovered_widgets' => count($this->discoveredWidgets),
            'total_widgets' => count($this->getAllWidgets()),
            'discovery_enabled' => true,
            'cache_enabled' => config('app.env') === 'production',
        ];
    }
    
    /**
     * Add hasAnyPermission method to User model helper
     */
    private function userHasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }
}