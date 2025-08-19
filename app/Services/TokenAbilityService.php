<?php

namespace App\Services;

class TokenAbilityService
{
    /**
     * Token abilities for different types of access
     */
    public const ABILITIES = [
        // Timer System
        'timers:read' => 'View timer data',
        'timers:write' => 'Create and update timers',
        'timers:delete' => 'Delete timers',
        'timers:sync' => 'Cross-device timer synchronization',

        // Time Entries
        'time-entries:read' => 'View time entries',
        'time-entries:write' => 'Create and update time entries',
        'time-entries:delete' => 'Delete time entries',

        // Projects & Tasks
        'projects:read' => 'View projects',
        'projects:write' => 'Create and update projects',
        'tasks:read' => 'View tasks',
        'tasks:write' => 'Create and update tasks',

        // Accounts & Users
        'accounts:read' => 'View account data',
        'accounts:write' => 'Manage account settings',
        'users:read' => 'View user data',
        'users:write' => 'Manage users',

        // Client Monitoring (Future External API)
        // Note: These abilities are prepared for future client monitoring features
        // 'client:read' => 'View client data',
        // 'endpoints:read' => 'View endpoint status',
        // 'endpoints:monitor' => 'Monitor endpoint health',
        // 'metrics:read' => 'Access monitoring metrics',
        // 'alerts:write' => 'Create monitoring alerts',

        // Billing & Financial Management
        'billing:read' => 'View billing data and reports',
        'billing:write' => 'Manage billing settings and configuration',
        'billing:admin' => 'Full billing administrative access',

        // Invoice Management
        'invoices:read' => 'View invoices',
        'invoices:write' => 'Create and modify invoices',
        'invoices:send' => 'Send invoices to customers',
        'invoices:void' => 'Void and cancel invoices',

        // Payment Management
        'payments:read' => 'View payment records',
        'payments:write' => 'Process and manage payments',
        'payments:refund' => 'Process payment refunds',

        // Billing Rates & Addons
        'rates:read' => 'View billing rates',
        'rates:write' => 'Manage billing rates',
        'addons:read' => 'View billing addons and templates',
        'addons:write' => 'Manage billing addons and templates',

        // Administration
        'admin:read' => 'Administrative read access',
        'admin:write' => 'Administrative write access',
        'settings:read' => 'View system settings',
        'settings:write' => 'Manage system settings',
    ];

    /**
     * Predefined token scopes for common use cases
     */
    public const SCOPES = [
        'employee' => [
            'timers:read',
            'timers:write',
            'timers:delete',
            'timers:sync',
            'time-entries:read',
            'time-entries:write',
            'projects:read',
            'tasks:read',
            'accounts:read',
            'billing:read',
            'invoices:read',
            'rates:read',
        ],

        'manager' => [
            'timers:read',
            'timers:write',
            'time-entries:read',
            'time-entries:write',
            'time-entries:delete',
            'projects:read',
            'projects:write',
            'tasks:read',
            'tasks:write',
            'users:read',
            'accounts:read',
            'billing:read',
            'billing:write',
            'invoices:read',
            'invoices:write',
            'payments:read',
            'payments:write',
            'rates:read',
            'rates:write',
            'addons:read',
        ],

        'billing-admin' => [
            'billing:read',
            'billing:write',
            'billing:admin',
            'invoices:read',
            'invoices:write',
            'invoices:send',
            'invoices:void',
            'payments:read',
            'payments:write',
            'payments:refund',
            'rates:read',
            'rates:write',
            'addons:read',
            'addons:write',
            'accounts:read',
        ],

        // Future scopes for external client monitoring
        // 'client-monitoring' => [...],
        // 'external-api' => [...],

        'mobile-app' => [
            'timers:read',
            'timers:write',
            'timers:sync',
            'time-entries:read',
            'time-entries:write',
            'projects:read',
            'tasks:read',
            'accounts:read',
        ],

        'admin' => [
            'admin:read',
            'admin:write',
            'settings:read',
            'settings:write',
            'users:read',
            'users:write',
            'accounts:read',
            'accounts:write',
            'billing:read',
            'billing:write',
            'rates:read',
            'rates:write',
        ],
    ];

    /**
     * Get all available abilities
     */
    public static function getAllAbilities(): array
    {
        return self::ABILITIES;
    }

    /**
     * Get abilities for a specific scope
     */
    public static function getAbilitiesForScope(string $scope): array
    {
        return self::SCOPES[$scope] ?? [];
    }

    /**
     * Get all available scopes
     */
    public static function getAllScopes(): array
    {
        return array_keys(self::SCOPES);
    }

    /**
     * Get description for an ability
     */
    public static function getAbilityDescription(string $ability): string
    {
        return self::ABILITIES[$ability] ?? 'Unknown ability';
    }

    /**
     * Check if a scope exists
     */
    public static function scopeExists(string $scope): bool
    {
        return array_key_exists($scope, self::SCOPES);
    }

    /**
     * Check if an ability exists
     */
    public static function abilityExists(string $ability): bool
    {
        return array_key_exists($ability, self::ABILITIES);
    }

    /**
     * Validate abilities array
     */
    public static function validateAbilities(array $abilities): array
    {
        return array_intersect($abilities, array_keys(self::ABILITIES));
    }
}
