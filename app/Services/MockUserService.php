<?php

namespace App\Services;

use App\Models\User;
use App\Models\RoleTemplate;
use App\Models\Role;
use Illuminate\Support\Collection;

class MockUserService
{
    /**
     * Create a mock user instance for preview purposes
     */
    public function createMockUser(RoleTemplate $roleTemplate, string $context = null): User
    {
        // Create a temporary user instance (not saved to database)
        $mockUser = new User([
            'name' => $this->getMockUserName($roleTemplate),
            'email' => $this->getMockUserEmail($roleTemplate),
        ]);
        
        // Set an ID for relationships (negative to avoid conflicts)
        $mockUser->id = -1;
        $mockUser->exists = true;
        
        // Create mock role and attach to user
        $mockRole = $this->createMockRole($roleTemplate, $context);
        
        // Override the roles relationship to return our mock role
        $mockUser->setRelation('roles', collect([$mockRole]));
        
        return $mockUser;
    }
    
    /**
     * Create a mock role for the given role template
     */
    private function createMockRole(RoleTemplate $roleTemplate, string $context = null): Role
    {
        $mockRole = new Role([
            'name' => $roleTemplate->name . ' (Preview)',
            'role_template_id' => $roleTemplate->id,
            'is_active' => true,
        ]);
        
        // Set mock ID
        $mockRole->id = -1;
        $mockRole->exists = true;
        
        // Attach the role template
        $mockRole->setRelation('template', $roleTemplate);
        
        return $mockRole;
    }
    
    /**
     * Generate mock user name based on role template
     */
    public function getMockUserName(RoleTemplate $roleTemplate): string
    {
        $nameMap = [
            'Super Admin' => 'System Administrator',
            'Admin' => 'Alex Johnson (Admin)',
            'Manager' => 'Sarah Wilson (Manager)', 
            'Employee' => 'John Doe (Employee)',
            'Account Manager' => 'Michael Brown (Account Manager)',
            'Account User' => 'Lisa Davis (Account User)',
        ];
        
        return $nameMap[$roleTemplate->name] ?? "Sample User ({$roleTemplate->name})";
    }
    
    /**
     * Generate mock user email based on role template
     */
    public function getMockUserEmail(RoleTemplate $roleTemplate): string
    {
        $emailMap = [
            'Super Admin' => 'admin@servicevault.local',
            'Admin' => 'alex.johnson@servicevault.local',
            'Manager' => 'sarah.wilson@servicevault.local',
            'Employee' => 'john.doe@servicevault.local',
            'Account Manager' => 'michael.brown@company.com',
            'Account User' => 'lisa.davis@company.com',
        ];
        
        return $emailMap[$roleTemplate->name] ?? 'user@example.com';
    }
    
    /**
     * Get all effective permissions for the role template
     */
    public function getMockPermissions(RoleTemplate $roleTemplate): array
    {
        return $roleTemplate->getAllPermissions();
    }
    
    /**
     * Check if mock user has a specific permission
     */
    public function mockUserHasPermission(RoleTemplate $roleTemplate, string $permission): bool
    {
        return in_array($permission, $roleTemplate->getAllPermissions());
    }
    
    /**
     * Check if mock user has any of the given permissions
     */
    public function mockUserHasAnyPermission(RoleTemplate $roleTemplate, array $permissions): bool
    {
        $userPermissions = $roleTemplate->getAllPermissions();
        
        return count(array_intersect($permissions, $userPermissions)) > 0;
    }
    
    /**
     * Get mock account context based on role template context
     */
    public function getMockAccountContext(RoleTemplate $roleTemplate, string $context): array
    {
        if ($context === 'service_provider' || $roleTemplate->context === 'service_provider') {
            return [
                'userType' => 'service_provider',
                'selectedAccount' => null,
                'availableAccounts' => $this->getMockServiceProviderAccounts(),
                'canSwitchAccounts' => true,
            ];
        }
        
        return [
            'userType' => 'account_user',
            'selectedAccount' => $this->getMockCustomerAccount(),
            'availableAccounts' => [$this->getMockCustomerAccount()],
            'canSwitchAccounts' => false,
        ];
    }
    
    /**
     * Get mock accounts for service provider context
     */
    private function getMockServiceProviderAccounts(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Acme Corporation',
                'domain' => 'acme.com',
                'created_at' => now()->subMonths(6)->toISOString(),
            ],
            [
                'id' => 2,
                'name' => 'Tech Solutions Inc',
                'domain' => 'techsolutions.com',
                'created_at' => now()->subMonths(3)->toISOString(),
            ],
            [
                'id' => 3,
                'name' => 'Global Services LLC',
                'domain' => 'globalservices.com',
                'created_at' => now()->subMonths(1)->toISOString(),
            ],
        ];
    }
    
    /**
     * Get mock account for customer context
     */
    private function getMockCustomerAccount(): array
    {
        return [
            'id' => 1,
            'name' => 'Acme Corporation',
            'domain' => 'acme.com',
            'created_at' => now()->subMonths(6)->toISOString(),
        ];
    }
    
    /**
     * Determine if mock user is super admin
     */
    public function isMockUserSuperAdmin(RoleTemplate $roleTemplate): bool
    {
        return $roleTemplate->name === 'Super Admin' || $roleTemplate->isSuperAdmin();
    }
    
    /**
     * Get mock user metadata for dashboard display
     */
    public function getMockUserMetadata(RoleTemplate $roleTemplate, string $context): array
    {
        return [
            'name' => $this->getMockUserName($roleTemplate),
            'email' => $this->getMockUserEmail($roleTemplate),
            'role' => $roleTemplate->name,
            'context' => $context,
            'lastLogin' => now()->subHours(2)->format('M j, Y g:i A'),
            'permissions_count' => count($roleTemplate->getAllPermissions()),
            'is_preview' => true,
        ];
    }
}