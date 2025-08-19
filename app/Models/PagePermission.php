<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_route',
        'page_name',
        'permission_key',
        'required_permissions',
        'category',
        'context',
        'is_menu_item',
        'menu_order',
    ];

    protected $casts = [
        'required_permissions' => 'array',
        'is_menu_item' => 'boolean',
        'menu_order' => 'integer',
    ];

    /**
     * Get role templates that have access to this page
     */
    public function roleTemplates()
    {
        return $this->belongsToMany(RoleTemplate::class, 'role_template_pages', 'page_permission_id', 'role_template_id');
    }

    /**
     * Scope for menu items
     */
    public function scopeMenuItems($query)
    {
        return $query->where('is_menu_item', true)->orderBy('menu_order');
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
     * Check if page is available in given context
     */
    public function isAvailableInContext(string $context): bool
    {
        return $this->context === 'both' || $this->context === $context;
    }

    /**
     * Check if user has required permissions for this page
     */
    public function userHasAccess($user): bool
    {
        if (empty($this->required_permissions)) {
            return true;
        }

        foreach ($this->required_permissions as $permission) {
            if (! $user->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the display name for this page
     */
    public function getDisplayName(): string
    {
        return $this->page_name ?: ucwords(str_replace(['-', '_', '.'], ' ', $this->page_route));
    }

    /**
     * Get formatted route for frontend routing
     */
    public function getFormattedRoute(): string
    {
        return str_replace('.', '/', $this->page_route);
    }

    /**
     * Get breadcrumb path for this page
     */
    public function getBreadcrumbPath(): array
    {
        $parts = explode('.', $this->page_route);
        $breadcrumb = [];

        foreach ($parts as $index => $part) {
            $breadcrumb[] = [
                'name' => ucwords(str_replace(['-', '_'], ' ', $part)),
                'route' => implode('.', array_slice($parts, 0, $index + 1)),
                'active' => $index === count($parts) - 1,
            ];
        }

        return $breadcrumb;
    }
}
