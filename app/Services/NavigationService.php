<?php

namespace App\Services;

use App\Models\User;

class NavigationService
{
    /**
     * Get navigation items filtered by user permissions
     */
    public function getNavigationForUser(User $user): array
    {
        $navigationItems = $this->getNavigationItems();
        
        return array_values(array_filter($navigationItems, function ($item) use ($user) {
            return $this->userCanAccessNavigationItem($user, $item);
        }));
    }

    /**
     * Check if user can access a navigation item
     */
    protected function userCanAccessNavigationItem(User $user, array $item): bool
    {
        // Super admin can access everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        // If no permissions required, everyone can access
        if (empty($item['permissions'])) {
            return true;
        }

        // Check if user has any of the required permissions
        return $user->hasAnyPermission($item['permissions']);
    }

    /**
     * Define all navigation items with their permission requirements
     */
    protected function getNavigationItems(): array
    {
        return [
            [
                'key' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'HomeIcon',
                'route' => 'dashboard',
                'active_patterns' => ['dashboard'],
                'permissions' => [], // Everyone can access dashboard
                'sort_order' => 1,
                'group' => 'main'
            ],
            [
                'key' => 'tickets',
                'label' => 'Tickets',
                'icon' => 'DocumentTextIcon',
                'route' => 'tickets.index',
                'active_patterns' => ['tickets.*'],
                'permissions' => ['tickets.view.all', 'tickets.view.assigned', 'tickets.view.account', 'tickets.view.own'],
                'sort_order' => 2,
                'group' => 'service_delivery'
            ],
            [
                'key' => 'timers',
                'label' => 'Timers',
                'icon' => 'ClockIcon',
                'route' => 'timers.web.index',
                'active_patterns' => ['timers.*'],
                'permissions' => ['time.track', 'timers.create', 'timers.view', 'timers.manage.own'],
                'sort_order' => 3,
                'group' => 'time_management'
            ],
            [
                'key' => 'time-entries',
                'label' => 'Time Entries',
                'icon' => 'CalendarIcon',
                'route' => 'time-entries.index',
                'active_patterns' => ['time-entries.*'],
                'permissions' => ['time_entries.view', 'time_entries.view.own', 'time_entries.approve'],
                'sort_order' => 4,
                'group' => 'time_management'
            ],
            [
                'key' => 'projects',
                'label' => 'Projects',
                'icon' => 'FolderIcon',
                'route' => 'projects.index',
                'active_patterns' => ['projects.*'],
                'permissions' => ['projects.view', 'projects.manage', 'projects.create'],
                'sort_order' => 5,
                'group' => 'project_management'
            ],
            [
                'key' => 'accounts',
                'label' => 'Accounts',
                'icon' => 'BuildingOfficeIcon',
                'route' => 'accounts.index',
                'active_patterns' => ['accounts.*'],
                'permissions' => ['accounts.manage', 'accounts.view', 'accounts.create'],
                'sort_order' => 6,
                'group' => 'administration'
            ],
            [
                'key' => 'users',
                'label' => 'Users',
                'icon' => 'UserGroupIcon',
                'route' => 'users.index',
                'active_patterns' => ['users.*'],
                'permissions' => ['users.manage', 'users.create', 'users.view.all'],
                'sort_order' => 7,
                'group' => 'administration'
            ],
            [
                'key' => 'billing',
                'label' => 'Billing',
                'icon' => 'CurrencyDollarIcon',
                'route' => 'billing.index',
                'active_patterns' => ['billing.*', 'invoices.*'],
                'permissions' => ['billing.manage', 'billing.view', 'invoices.create'],
                'sort_order' => 8,
                'group' => 'financial'
            ],
            [
                'key' => 'reports',
                'label' => 'Reports',
                'icon' => 'ChartBarIcon',
                'route' => 'reports.index',
                'active_patterns' => ['reports.*'],
                'permissions' => ['reports.view', 'reports.create', 'admin.read'],
                'sort_order' => 9,
                'group' => 'analytics'
            ],
            [
                'key' => 'settings',
                'label' => 'Settings',
                'icon' => 'CogIcon',
                'route' => 'settings.index',
                'active_patterns' => ['settings.*'],
                'permissions' => ['admin.manage', 'system.manage', 'settings.manage'],
                'sort_order' => 10,
                'group' => 'administration'
            ]
        ];
    }

    /**
     * Get navigation items grouped by category
     */
    public function getGroupedNavigationForUser(User $user): array
    {
        $items = $this->getNavigationForUser($user);
        
        $grouped = [];
        foreach ($items as $item) {
            $group = $item['group'] ?? 'other';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $item;
        }

        // Sort each group by sort_order
        foreach ($grouped as &$group) {
            usort($group, fn($a, $b) => ($a['sort_order'] ?? 999) <=> ($b['sort_order'] ?? 999));
        }

        return $grouped;
    }

    /**
     * Get group labels for display
     */
    public function getGroupLabels(): array
    {
        return [
            'main' => 'Main',
            'service_delivery' => 'Service Delivery',
            'time_management' => 'Time Management',
            'project_management' => 'Project Management',
            'administration' => 'Administration',
            'financial' => 'Financial',
            'analytics' => 'Analytics'
        ];
    }

    /**
     * Check if user can access a specific route
     */
    public function userCanAccessRoute(User $user, string $route): bool
    {
        $items = $this->getNavigationForUser($user);
        
        foreach ($items as $item) {
            if ($item['route'] === $route) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get breadcrumb data for current route
     */
    public function getBreadcrumbsForRoute(User $user, string $currentRoute): array
    {
        $items = $this->getNavigationForUser($user);
        $breadcrumbs = [['label' => 'Dashboard', 'route' => 'dashboard']];
        
        foreach ($items as $item) {
            foreach ($item['active_patterns'] as $pattern) {
                if (fnmatch($pattern, $currentRoute)) {
                    if ($item['route'] !== 'dashboard') {
                        $breadcrumbs[] = [
                            'label' => $item['label'], 
                            'route' => $item['route']
                        ];
                    }
                    break 2;
                }
            }
        }
        
        return $breadcrumbs;
    }
}