<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAddon extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'ticket_id',
        'added_by_user_id',
        'name',
        'description',
        'category',
        'sku',
        'unit_price',
        'quantity',
        'discount_amount',
        'total_amount',
        'billable',
        'is_taxable',
        'billing_category',
        'addon_template_id',
        'status',
        'approved_by_user_id',
        'approved_at',
        'approval_notes',
        'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billable' => 'boolean',
        'is_taxable' => 'boolean',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'approved',
        'billable' => true,
        'is_taxable' => true,
        'quantity' => 1,
        'discount_amount' => 0,
    ];

    protected $appends = [
        'can_unapprove',
        'is_invoiced',
    ];

    /**
     * Calculate and set the total amount
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (TicketAddon $addon) {
            $addon->calculateTotal();
        });
    }

    /**
     * Calculate the total amount based on unit price, quantity, and discount
     * Note: Tax is calculated at the invoice level, not per addon
     */
    public function calculateTotal(): void
    {
        $subtotal = $this->unit_price * $this->quantity;
        $this->total_amount = $subtotal - $this->discount_amount;
    }

    /**
     * Get the service ticket this addon belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who added this addon
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }

    /**
     * Get the user who approved this addon
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * Get the template this addon was created from
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(AddonTemplate::class, 'addon_template_id');
    }

    /**
     * Scope for billable addons
     */
    public function scopeBillable($query)
    {
        return $query->where('billable', true);
    }

    /**
     * Scope for approved addons
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for pending addons
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Check if the addon can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['pending', 'rejected']);
    }

    /**
     * Check if the addon can be approved
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Approve the addon
     */
    public function approve(User $approver, ?string $notes = null): bool
    {
        if (! $this->canBeApproved()) {
            return false;
        }

        $this->status = 'approved';
        $this->approved_by_user_id = $approver->id;
        $this->approved_at = now();
        $this->approval_notes = $notes;

        return $this->save();
    }

    /**
     * Reject the addon
     */
    public function reject(User $approver, ?string $notes = null): bool
    {
        if (! $this->canBeApproved()) {
            return false;
        }

        $this->status = 'rejected';
        $this->approved_by_user_id = $approver->id;
        $this->approved_at = now();
        $this->approval_notes = $notes;

        return $this->save();
    }

    /**
     * Get formatted total amount
     */
    protected function formattedTotal(): Attribute
    {
        return Attribute::make(
            get: fn () => '$'.number_format($this->total_amount, 2)
        );
    }

    /**
     * Get formatted unit price
     */
    protected function formattedUnitPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => '$'.number_format($this->unit_price, 2)
        );
    }

    /**
     * Get can_unapprove attribute for API responses
     */
    protected function canUnapproveAttribute(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->canUnapprove()
        );
    }

    /**
     * Get is_invoiced attribute for API responses
     */
    protected function isInvoicedAttribute(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->isInvoiced()
        );
    }

    /**
     * Check if the addon is associated with any invoice.
     */
    public function isInvoiced(): bool
    {
        return $this->invoiceLineItems()->exists();
    }

    /**
     * Check if the addon can be unapproved.
     */
    public function canUnapprove(): bool
    {
        return $this->status === 'approved' && !$this->isInvoiced();
    }

    /**
     * Unapprove the addon (only if not invoiced).
     */
    public function unapprove(User $unapprover, ?string $notes = null): bool
    {
        if (!$this->canUnapprove()) {
            return false;
        }

        $this->status = 'pending';
        $this->approved_by_user_id = null;
        $this->approved_at = null;
        $this->approval_notes = $notes;

        return $this->save();
    }

    /**
     * Get the invoice line items for this addon.
     */
    public function invoiceLineItems()
    {
        return $this->hasMany(InvoiceLineItem::class, 'ticket_addon_id');
    }
}
