<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug', 
        'description',
        'parent_id',
        'settings',
        'theme_settings',
        'is_active',
    ];
    
    protected $casts = [
        'settings' => 'array',
        'theme_settings' => 'array',
        'is_active' => 'boolean',
    ];
    
    // Hierarchical relationships
    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }
    
    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }
    
    public function descendants()
    {
        return $this->hasMany(Account::class, 'parent_id')->with('descendants');
    }
    
    public function ancestors()
    {
        $ancestors = collect();
        $parent = $this->parent;
        
        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }
        
        return $ancestors;
    }
    
    // User relationships
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    
    public function roles()
    {
        return $this->hasMany(Role::class);
    }
}
