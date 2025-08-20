<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\RoleTemplate;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add import permissions to Super Admin role
        $superAdmin = RoleTemplate::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $permissions = $superAdmin->permissions ?? [];
            
            // Import permissions to add
            $importPermissions = [
                'system.import',
                'system.import.configure', 
                'system.import.execute',
                'import.profiles.manage',
                'import.jobs.execute',
                'import.jobs.monitor',
            ];
            
            // Add permissions if they don't exist
            foreach ($importPermissions as $permission) {
                if (!in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }
            
            $superAdmin->update(['permissions' => $permissions]);
        }
        
        // Add import page permissions to Super Admin
        $superAdmin = RoleTemplate::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $pagePermissions = $superAdmin->page_permissions ?? [];
            
            $importPagePermissions = [
                'pages.import.manage',
                'pages.settings.import',
            ];
            
            foreach ($importPagePermissions as $permission) {
                if (!in_array($permission, $pagePermissions)) {
                    $pagePermissions[] = $permission;
                }
            }
            
            $superAdmin->update(['page_permissions' => $pagePermissions]);
        }
        
        // Optionally add limited import permissions to Admin role
        $admin = RoleTemplate::where('name', 'Admin')->first();
        if ($admin) {
            $permissions = $admin->permissions ?? [];
            
            $adminImportPermissions = [
                'system.import', // View only access
                'import.jobs.monitor', // Monitor existing jobs
            ];
            
            foreach ($adminImportPermissions as $permission) {
                if (!in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }
            
            $admin->update(['permissions' => $permissions]);
            
            // Add import page permission to Admin
            $pagePermissions = $admin->page_permissions ?? [];
            if (!in_array('pages.import.manage', $pagePermissions)) {
                $pagePermissions[] = 'pages.import.manage';
                $admin->update(['page_permissions' => $pagePermissions]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove import permissions from Super Admin
        $superAdmin = RoleTemplate::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $permissions = $superAdmin->permissions ?? [];
            $importPermissions = [
                'system.import',
                'system.import.configure', 
                'system.import.execute',
                'import.profiles.manage',
                'import.jobs.execute',
                'import.jobs.monitor',
            ];
            
            $permissions = array_diff($permissions, $importPermissions);
            $superAdmin->update(['permissions' => array_values($permissions)]);
            
            // Remove page permissions
            $pagePermissions = $superAdmin->page_permissions ?? [];
            $importPagePermissions = [
                'pages.import.manage',
                'pages.settings.import',
            ];
            
            $pagePermissions = array_diff($pagePermissions, $importPagePermissions);
            $superAdmin->update(['page_permissions' => array_values($pagePermissions)]);
        }
        
        // Remove import permissions from Admin
        $admin = RoleTemplate::where('name', 'Admin')->first();
        if ($admin) {
            $permissions = $admin->permissions ?? [];
            $adminImportPermissions = [
                'system.import',
                'import.jobs.monitor',
            ];
            
            $permissions = array_diff($permissions, $adminImportPermissions);
            $admin->update(['permissions' => array_values($permissions)]);
            
            // Remove page permission
            $pagePermissions = $admin->page_permissions ?? [];
            $pagePermissions = array_diff($pagePermissions, ['pages.import.manage']);
            $admin->update(['page_permissions' => array_values($pagePermissions)]);
        }
    }
};
