<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketComment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'content',
        'external_id',
        'is_internal',
        'attachments',
        'parent_id',
        'edited_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_internal' => 'boolean',
        'edited_at' => 'datetime',
    ];

    /**
     * Get the ticket that this comment belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who created this comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment (for threading)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class, 'parent_id');
    }

    /**
     * Get the replies to this comment
     */
    public function replies(): HasMany
    {
        return $this->hasMany(TicketComment::class, 'parent_id')->orderBy('created_at');
    }

    /**
     * Scope to get only top-level comments (not replies)
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get internal comments only
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope to get external/customer comments only
     */
    public function scopeExternal($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Check if comment was edited
     */
    public function wasEdited(): bool
    {
        return $this->edited_at !== null;
    }

    /**
     * Check if user can edit this comment
     */
    public function canBeEditedBy(User $user): bool
    {
        // Users can edit their own comments within 1 hour, or admins can edit any comment
        return ($this->user_id === $user->id && $this->created_at->diffInHours(now()) <= 1)
               || $user->hasAnyPermission(['admin.write', 'tickets.manage']);
    }

    /**
     * Check if user can delete this comment
     */
    public function canBeDeletedBy(User $user): bool
    {
        // Users can delete their own comments, or admins can delete any comment
        return $this->user_id === $user->id
               || $user->hasAnyPermission(['admin.write', 'tickets.manage']);
    }
}
