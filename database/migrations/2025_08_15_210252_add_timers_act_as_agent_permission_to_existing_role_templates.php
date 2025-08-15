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
     * Add 'timers.act_as_agent' permission to existing role templates that should have agent privileges.
     * This ensures backward compatibility for existing installations.
     */
    public function up(): void
    {
        // Get existing role templates that should have agent privileges
        $roleTemplatesWithAgentPrivileges = [
            'Super Admin',
            'Admin', 
            'Agent'
        ];
        
        foreach ($roleTemplatesWithAgentPrivileges as $roleName) {
            $roleTemplate = RoleTemplate::where('name', $roleName)->first();
            
            if ($roleTemplate) {
                $permissions = $roleTemplate->permissions ?? [];
                
                // Add the permission if it doesn't already exist
                if (!in_array('timers.act_as_agent', $permissions)) {
                    $permissions[] = 'timers.act_as_agent';
                    $roleTemplate->permissions = $permissions;
                    $roleTemplate->save();
                    
                    echo "Added 'timers.act_as_agent' permission to {$roleName} role template.\n";
                } else {
                    echo "Permission 'timers.act_as_agent' already exists in {$roleName} role template.\n";
                }
            } else {
                echo "Role template '{$roleName}' not found, skipping.\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Remove the 'timers.act_as_agent' permission from role templates.
     */
    public function down(): void
    {
        $roleTemplatesWithAgentPrivileges = [
            'Super Admin',
            'Admin', 
            'Agent'
        ];
        
        foreach ($roleTemplatesWithAgentPrivileges as $roleName) {
            $roleTemplate = RoleTemplate::where('name', $roleName)->first();
            
            if ($roleTemplate) {
                $permissions = $roleTemplate->permissions ?? [];
                
                // Remove the permission if it exists
                $permissions = array_filter($permissions, function($permission) {
                    return $permission !== 'timers.act_as_agent';
                });
                
                $roleTemplate->permissions = array_values($permissions);
                $roleTemplate->save();
                
                echo "Removed 'timers.act_as_agent' permission from {$roleName} role template.\n";
            }
        }
    }
};
