<?php

use App\Models\RoleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update Super Admin role template with missing critical permissions
        $superAdmin = RoleTemplate::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $permissions = $superAdmin->permissions ?? [];

            // Add missing admin.write permission (needed for agent APIs)
            if (! in_array('admin.write', $permissions)) {
                $permissions[] = 'admin.write';
            }

            // Add missing timer permissions (needed for timer assignment UI)
            $timerPermissions = ['timers.admin', 'timers.read', 'timers.write'];
            foreach ($timerPermissions as $permission) {
                if (! in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }

            $superAdmin->update(['permissions' => $permissions]);
        }

        // Update Admin role template with timer admin permission for consistency
        $admin = RoleTemplate::where('name', 'Admin')->first();
        if ($admin) {
            $permissions = $admin->permissions ?? [];

            // Add timers.admin permission for consistency
            if (! in_array('timers.admin', $permissions)) {
                $permissions[] = 'timers.admin';
            }

            $admin->update(['permissions' => $permissions]);
        }

        // Update Agent role template with timer admin permission
        $agent = RoleTemplate::where('name', 'Agent')->first();
        if ($agent) {
            $permissions = $agent->permissions ?? [];

            // Add timers.admin permission for service agents
            if (! in_array('timers.admin', $permissions)) {
                $permissions[] = 'timers.admin';
            }

            $agent->update(['permissions' => $permissions]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the added permissions from role templates
        $roleNames = ['Super Admin', 'Admin', 'Agent'];
        $permissionsToRemove = ['admin.write', 'timers.admin', 'timers.read', 'timers.write'];

        foreach ($roleNames as $roleName) {
            $role = RoleTemplate::where('name', $roleName)->first();
            if ($role) {
                $permissions = $role->permissions ?? [];

                // Remove the permissions we added
                $permissions = array_diff($permissions, $permissionsToRemove);

                $role->update(['permissions' => array_values($permissions)]);
            }
        }
    }
};
