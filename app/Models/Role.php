<?php

namespace App\Models;

use App\Traits\HasUuid;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory, HasUuid;
    
    protected $fillable = [
        'account_id',
        'role_template_id',
        'name',
        'custom_permissions',
        'is_active',
    ];
    
    protected $casts = [
        'custom_permissions' => 'array',
        'is_active' => 'boolean',
    ];
    
    /**
     * The account this role belongs to.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
    
    /**
     * The role template this role is based on.
     */
    public function roleTemplate()
    {
        return $this->belongsTo(RoleTemplate::class);
    }
    
    /**
     * Alias for roleTemplate relationship for backward compatibility
     */
    public function template()
    {
        return $this->roleTemplate();
    }
    
    /**
     * Users assigned to this role.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
    
    /**
     * Get effective permissions for this role (template + custom overrides).
     */
    public function getEffectivePermissions(): array
    {
        $basePermissions = $this->roleTemplate->permissions ?? [];
        $customPermissions = $this->custom_permissions ?? [];
        
        return array_unique(array_merge($basePermissions, $customPermissions));
    }
}
