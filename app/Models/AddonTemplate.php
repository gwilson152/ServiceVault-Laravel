<?php

namespace App\Models;

use App\Traits\HasUuid;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AddonTemplate extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'description',
        'category',
        'sku',
        'default_unit_price',
        'default_quantity',
        'allow_quantity_override',
        'allow_price_override',
        'is_billable',
        'is_taxable',
        'billing_category',
        'default_tax_rate',
        'is_active',
        'sort_order',
        'metadata'
    ];

    protected $casts = [
        'default_unit_price' => 'decimal:2',
        'default_quantity' => 'decimal:2',
        'default_tax_rate' => 'decimal:4',
        'allow_quantity_override' => 'boolean',
        'allow_price_override' => 'boolean',
        'is_billable' => 'boolean',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * Get all ticket addons created from this template
     */
    public function ticketAddons(): HasMany
    {
        return $this->hasMany(TicketAddon::class);
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for templates by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get ordered templates
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Create a TicketAddon instance from this template
     */
    public function createAddonForTicket(Ticket $ticket, User $user, array $overrides = []): TicketAddon
    {
        $addonData = [
            'ticket_id' => $ticket->id,
            'added_by_user_id' => $user->id,
            'addon_template_id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'sku' => $this->sku,
            'unit_price' => $this->default_unit_price,
            'quantity' => $this->default_quantity,
            'is_billable' => $this->is_billable,
            'is_taxable' => $this->is_taxable,
            'billing_category' => $this->billing_category,
            'tax_rate' => $this->default_tax_rate,
        ];

        // Apply overrides with validation
        if (isset($overrides['quantity']) && $this->allow_quantity_override) {
            $addonData['quantity'] = $overrides['quantity'];
        }

        if (isset($overrides['unit_price']) && $this->allow_price_override) {
            $addonData['unit_price'] = $overrides['unit_price'];
        }

        // Allow other field overrides
        foreach (['name', 'description', 'discount_amount', 'metadata'] as $field) {
            if (isset($overrides[$field])) {
                $addonData[$field] = $overrides[$field];
            }
        }

        return TicketAddon::create($addonData);
    }

    /**
     * Get formatted default price
     */
    public function getFormattedDefaultPriceAttribute(): string
    {
        return '$' . number_format($this->default_unit_price, 2);
    }

    /**
     * Get available categories
     */
    public static function getCategories(): array
    {
        return [
            'product' => 'Product',
            'service' => 'Service',
            'license' => 'License',
            'hardware' => 'Hardware',
            'software' => 'Software',
            'expense' => 'Expense',
            'other' => 'Other'
        ];
    }

    /**
     * Get category display name
     */
    public function getCategoryDisplayNameAttribute(): string
    {
        $categories = self::getCategories();
        return $categories[$this->category] ?? ucfirst($this->category);
    }
}
