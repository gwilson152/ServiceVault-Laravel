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
        
        $filteredItems = array_filter($navigationItems, function ($item) use ($user) {
            return $this->userCanAccessNavigationItem($user, $item);
        });

        // Add filtered subitems to each navigation item
        foreach ($filteredItems as &$item) {
            $item['subitems'] = $this->getFilteredSubitems($user, $item);
        }

        return array_values($filteredItems);
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
     * Get filtered subitems for a navigation item
     */
    protected function getFilteredSubitems(User $user, array $item): array
    {
        if (!isset($item['subitems']) || !is_array($item['subitems'])) {
            return [];
        }

        return array_values(array_filter($item['subitems'], function ($subitem) use ($user) {
            return $this->userCanAccessNavigationItem($user, $subitem);
        }));
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
                'active_patterns' => ['billing.*', 'invoices.*', 'payments.*'],
                'permissions' => [
                    'billing.manage', 'billing.view.account', 'billing.view.all', 'billing.admin',
                    'invoices.create', 'invoices.view.account', 'invoices.view.all',
                    'payments.create', 'payments.view.account', 'payments.view.all',
                    'billing.rates.view', 'billing.addons.view'
                ],
                'sort_order' => 5,
                'group' => 'financial',
                'subitems' => [
                    [
                        'key' => 'billing-overview',
                        'label' => 'Overview',
                        'route' => 'billing.index',
                        'permissions' => ['billing.view.account', 'billing.view.all']
                    ],
                    [
                        'key' => 'billing-invoices',
                        'label' => 'Invoices',
                        'route' => 'billing.index',
                        'params' => ['tab' => 'invoices'],
                        'permissions' => ['invoices.view.account', 'invoices.view.all', 'invoices.create']
                    ],
                    [
                        'key' => 'billing-payments',
                        'label' => 'Payments',
                        'route' => 'billing.index',
                        'params' => ['tab' => 'payments'],
                        'permissions' => ['payments.view.account', 'payments.view.all', 'payments.create']
                    ],
                    [
                        'key' => 'billing-rates',
                        'label' => 'Billing Rates',
                        'route' => 'settings.index',
                        'params' => ['tab' => 'billing'],
                        'permissions' => ['billing.rates.view', 'billing.rates.manage']
                    ]
                ]
            ],
            [
                'key' => 'my-billing',
                'label' => 'My Billing',
                'icon' => 'CreditCardIcon',
                'route' => 'portal.billing',
                'active_patterns' => ['portal.billing.*'],
                'permissions' => ['billing.view.own', 'invoices.view.own', 'payments.view.own'],
                'sort_order' => 6,
                'group' => 'customer_portal',
                'subitems' => [
                    [
                        'key' => 'my-invoices',
                        'label' => 'My Invoices',
                        'route' => 'portal.billing',
                        'params' => ['tab' => 'invoices'],
                        'permissions' => ['invoices.view.own']
                    ],
                    [
                        'key' => 'my-payments',
                        'label' => 'Payment History',
                        'route' => 'portal.billing',
                        'params' => ['tab' => 'payments'],
                        'permissions' => ['payments.view.own']
                    ]
                ]
            ],
            [
                'key' => 'reports',
                'label' => 'Reports',
                'icon' => 'ChartBarIcon',
                'route' => 'reports.index',
                'active_patterns' => ['reports.*'],
                'permissions' => ['reports.view', 'reports.create', 'admin.read'],
                'sort_order' => 11,
                'group' => 'analytics'
            ],
            [
                'key' => 'roles',
                'label' => 'Roles & Permissions',
                'icon' => 'ShieldCheckIcon',
                'route' => 'roles.index',
                'active_patterns' => ['roles.*'],
                'permissions' => ['admin.manage', 'roles.manage', 'role_templates.manage'],
                'sort_order' => 8,
                'group' => 'administration'
            ],
            [
                'key' => 'settings',
                'label' => 'Settings',
                'icon' => 'CogIcon',
                'route' => 'settings.index',
                'active_patterns' => ['settings.*'],
                'permissions' => ['admin.manage', 'system.manage', 'settings.manage'],
                'sort_order' => 12,
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
            'financial' => 'Financial',
            'customer_portal' => 'Customer Portal',
            'analytics' => 'Analytics',
            'administration' => 'Administration'
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

    /**
     * Generate URL for navigation item with parameters
     */
    public function getNavigationItemUrl(array $item): string
    {
        $route = $item['route'];
        $params = $item['params'] ?? [];
        
        if (empty($params)) {
            return route($route);
        }
        
        return route($route, $params);
    }

    /**
     * Get navigation items with generated URLs
     */
    public function getNavigationWithUrls(User $user): array
    {
        $items = $this->getNavigationForUser($user);
        
        foreach ($items as &$item) {
            $item['url'] = $this->getNavigationItemUrl($item);
            
            // Generate URLs for subitems
            if (!empty($item['subitems'])) {
                foreach ($item['subitems'] as &$subitem) {
                    $subitem['url'] = $this->getNavigationItemUrl($subitem);
                }
            }
        }
        
        return $items;
    }
}