<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WidgetPermission extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'widget_id',
        'widget_name',
        'permission_key',
        'description',
        'category',
        'context',
        'required_permissions',
        'is_configurable',
        'default_enabled',
    ];
    
    protected $casts = [
        'required_permissions' => 'array',
        'is_configurable' => 'boolean',
        'default_enabled' => 'boolean',
    ];
    
    /**
     * Get role templates that have this widget permission
     */
    public function roleTemplates()
    {
        return $this->belongsToMany(RoleTemplate::class, 'role_template_widgets', 'widget_id', 'role_template_id', 'widget_id');
    }
    
    /**
     * Get widget configurations for this permission
     */
    public function configurations()
    {
        return $this->hasMany(RoleTemplateWidget::class, 'widget_id', 'widget_id');
    }
    
    /**
     * Scope for context filtering
     */
    public function scopeForContext($query, string $context)
    {
        return $query->where('context', $context)->orWhere('context', 'both');
    }
    
    /**
     * Scope for category filtering
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
    
    /**
     * Check if widget is available in given context
     */
    public function isAvailableInContext(string $context): bool
    {
        return $this->context === 'both' || $this->context === $context;
    }
    
    /**
     * Get the display name for this widget
     */
    public function getDisplayName(): string
    {
        return $this->widget_name ?: $this->widget_id;
    }
}
