<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\RoleTemplateFactory> */
    use HasFactory;
    
    protected $fillable = [
        'name',
        'permissions',
        'is_system_role',
        'is_default',
        'description',
    ];
    
    protected $casts = [
        'permissions' => 'array',
        'is_system_role' => 'boolean',
        'is_default' => 'boolean',
    ];
    
    public function roles()
    {
        return $this->hasMany(Role::class);
    }
}
