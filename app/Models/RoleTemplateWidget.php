<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleTemplateWidget extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'role_template_id',
        'widget_id',
        'enabled',
        'widget_config',
        'display_order',
    ];
    
    protected $casts = [
        'enabled' => 'boolean',
        'widget_config' => 'array',
        'display_order' => 'integer',
    ];
    
    /**
     * Get the role template that owns this widget configuration
     */
    public function roleTemplate()
    {
        return $this->belongsTo(RoleTemplate::class);
    }
    
    /**
     * Get the widget permission this configuration is for
     */
    public function widgetPermission()
    {
        return $this->belongsTo(WidgetPermission::class, 'widget_id', 'widget_id');
    }
    
    /**
     * Scope for enabled widgets
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
    
    /**
     * Scope for disabled widgets
     */
    public function scopeDisabled($query)
    {
        return $query->where('enabled', false);
    }
    
    /**
     * Scope for ordered display
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
    
    /**
     * Get widget configuration with defaults
     */
    public function getConfigWithDefaults(): array
    {
        $defaults = [
            'enabled' => $this->enabled,
            'display_order' => $this->display_order,
            'customizable' => true,
            'size' => ['w' => 4, 'h' => 3],
        ];
        
        return array_merge($defaults, $this->widget_config ?? []);
    }
    
    /**
     * Update widget configuration
     */
    public function updateConfig(array $config): bool
    {
        return $this->update(['widget_config' => array_merge($this->widget_config ?? [], $config)]);
    }
    
    /**
     * Toggle widget enabled status
     */
    public function toggle(): bool
    {
        return $this->update(['enabled' => !$this->enabled]);
    }
}
