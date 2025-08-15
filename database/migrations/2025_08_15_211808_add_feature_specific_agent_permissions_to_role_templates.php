<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\RoleTemplate;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add feature-specific agent permissions to existing role templates that should have agent privileges.
     */
    public function up(): void
    {
        $newAgentPermissions = [
            'timers.act_as_agent',
            'tickets.act_as_agent',
            'time.act_as_agent', 
            'billing.act_as_agent'
        ];
        
        $roleTemplatesWithAgentPrivileges = [
            'Super Admin',
            'Admin', 
            'Agent'
        ];
        
        foreach ($roleTemplatesWithAgentPrivileges as $roleName) {
            $roleTemplate = RoleTemplate::where('name', $roleName)->first();
            
            if ($roleTemplate) {
                $permissions = $roleTemplate->permissions ?? [];
                $added = [];
                
                foreach ($newAgentPermissions as $permission) {
                    if (!in_array($permission, $permissions)) {
                        $permissions[] = $permission;
                        $added[] = $permission;
                    }
                }
                
                if (!empty($added)) {
                    $roleTemplate->permissions = $permissions;
                    $roleTemplate->save();
                    
                    echo "Added " . implode(', ', $added) . " permissions to {$roleName} role template.\n";
                } else {
                    echo "All feature-specific agent permissions already exist in {$roleName} role template.\n";
                }
            } else {
                echo "Role template '{$roleName}' not found, skipping.\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Remove feature-specific agent permissions from role templates.
     */
    public function down(): void
    {
        $agentPermissionsToRemove = [
            'timers.act_as_agent',
            'tickets.act_as_agent',
            'time.act_as_agent',
            'billing.act_as_agent'
        ];
        
        $roleTemplatesWithAgentPrivileges = [
            'Super Admin',
            'Admin', 
            'Agent'
        ];
        
        foreach ($roleTemplatesWithAgentPrivileges as $roleName) {
            $roleTemplate = RoleTemplate::where('name', $roleName)->first();
            
            if ($roleTemplate) {
                $permissions = $roleTemplate->permissions ?? [];
                $removed = [];
                
                foreach ($agentPermissionsToRemove as $permission) {
                    if (($key = array_search($permission, $permissions)) !== false) {
                        unset($permissions[$key]);
                        $removed[] = $permission;
                    }
                }
                
                if (!empty($removed)) {
                    $roleTemplate->permissions = array_values($permissions);
                    $roleTemplate->save();
                    
                    echo "Removed " . implode(', ', $removed) . " permissions from {$roleName} role template.\n";
                }
            }
        }
    }
};
