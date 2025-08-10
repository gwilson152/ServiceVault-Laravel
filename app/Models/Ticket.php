<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'account_id',
        'ticket_number',
        'title',
        'description',
        'status',
        'priority',
        'category',
        'created_by_id',
        'assigned_to_id',
        'due_date',
        'estimated_hours',
        'estimated_amount',
        'actual_amount',
        'billing_rate_id',
        'started_at',
        'completed_at',
        'resolved_at',
        'closed_at',
        'metadata',
        'settings',
    ];
    
    protected $casts = [
        'due_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'metadata' => 'array',
        'settings' => 'array',
        'estimated_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
    ];
    
    // Status constants for better code organization
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING_CUSTOMER = 'waiting_customer';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';
    
    // Priority constants
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';
    
    /**
     * Relationships
     */
    
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
    
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
    
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }
    
    public function billingRate()
    {
        return $this->belongsTo(BillingRate::class);
    }
    
    /**
     * Users assigned to this ticket (many-to-many for team assignments)
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'ticket_user')
                    ->withPivot(['role', 'assigned_at', 'unassigned_at'])
                    ->withTimestamps();
    }
    
    /**
     * Time entries logged against this ticket
     */
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }
    
    /**
     * Active timers running against this ticket
     */
    public function timers()
    {
        return $this->hasMany(Timer::class);
    }
    
    /**
     * Get all addons for this ticket
     */
    public function addons()
    {
        return $this->hasMany(TicketAddon::class);
    }
    
    /**
     * Get approved addons for this ticket
     */
    public function approvedAddons()
    {
        return $this->hasMany(TicketAddon::class)->where('status', 'approved');
    }
    
    /**
     * Get ticket status model
     */
    public function statusModel()
    {
        return $this->belongsTo(TicketStatus::class, 'status', 'key');
    }
    
    /**
     * Get ticket category model
     */
    public function categoryModel()
    {
        return $this->belongsTo(TicketCategory::class, 'category', 'key');
    }
    
    /**
     * Business Logic Methods
     */
    
    /**
     * Generate a unique ticket number
     */
    public static function generateTicketNumber(): string
    {
        $year = date('Y');
        $prefix = "TKT-{$year}-";
        
        // Get the highest ticket number for this year
        $lastTicket = static::where('ticket_number', 'like', "{$prefix}%")
            ->orderBy('ticket_number', 'desc')
            ->first();
        
        if (!$lastTicket) {
            $number = 1;
        } else {
            $lastNumber = (int) str_replace($prefix, '', $lastTicket->ticket_number);
            $number = $lastNumber + 1;
        }
        
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Check if ticket is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }
    
    /**
     * Get total time logged on this ticket (in seconds)
     */
    public function getTotalTimeLogged(): int
    {
        return $this->timeEntries()->sum('duration');
    }
    
    /**
     * Get total billable time (in seconds)
     */
    public function getTotalBillableTime(): int
    {
        return $this->timeEntries()->where('billable', true)->sum('duration');
    }
    
    /**
     * Get total cost based on time entries and billing rates
     */
    public function getTotalCost(): float
    {
        $total = 0;
        
        // Add time entries cost
        foreach ($this->timeEntries()->with('billingRate')->get() as $entry) {
            if ($entry->billable && $entry->billingRate) {
                $hours = $entry->duration / 3600;
                $total += $hours * $entry->billingRate->rate;
            }
        }
        
        // Add approved addon costs
        $addonTotal = $this->approvedAddons()->sum('total');
        $total += $addonTotal;
        
        return round($total, 2);
    }
    
    /**
     * Get total addon costs
     */
    public function getTotalAddonCost(): float
    {
        return round($this->approvedAddons()->sum('total'), 2);
    }
    
    /**
     * Get pending addon costs (awaiting approval)
     */
    public function getPendingAddonCost(): float
    {
        return round($this->addons()->where('status', 'pending')->sum('total'), 2);
    }
    
    /**
     * Status workflow methods
     */
    
    public function canTransitionTo(string $newStatus): bool
    {
        $validTransitions = [
            self::STATUS_OPEN => [self::STATUS_IN_PROGRESS, self::STATUS_ON_HOLD, self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS => [self::STATUS_WAITING_CUSTOMER, self::STATUS_ON_HOLD, self::STATUS_RESOLVED, self::STATUS_CANCELLED],
            self::STATUS_WAITING_CUSTOMER => [self::STATUS_IN_PROGRESS, self::STATUS_ON_HOLD, self::STATUS_RESOLVED],
            self::STATUS_ON_HOLD => [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_RESOLVED => [self::STATUS_CLOSED, self::STATUS_IN_PROGRESS], // Can reopen if customer isn't satisfied
            self::STATUS_CLOSED => [], // Closed tickets can't be transitioned (except by admin override)
            self::STATUS_CANCELLED => [], // Cancelled tickets are final
        ];
        
        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }
    
    public function transitionTo(string $newStatus, User $user, ?string $notes = null): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }
        
        $oldStatus = $this->status;
        $this->status = $newStatus;
        
        // Update status-specific timestamps
        switch ($newStatus) {
            case self::STATUS_IN_PROGRESS:
                if ($oldStatus === self::STATUS_OPEN) {
                    $this->started_at = now();
                }
                break;
            case self::STATUS_RESOLVED:
                $this->resolved_at = now();
                break;
            case self::STATUS_CLOSED:
                $this->closed_at = now();
                break;
        }
        
        return $this->save();
    }
    
    /**
     * Assign ticket to a user
     */
    public function assignTo(User $user): bool
    {
        $this->assigned_to_id = $user->id;
        
        // Auto-transition to in_progress if currently open
        if ($this->status === self::STATUS_OPEN) {
            $this->status = self::STATUS_IN_PROGRESS;
            $this->started_at = now();
        }
        
        return $this->save();
    }
    
    /**
     * Check if user can view this ticket
     */
    public function canBeViewedBy(User $user): bool
    {
        // Account access check
        if (!$user->accounts()->where('accounts.id', $this->account_id)->exists()) {
            return false;
        }
        
        // Super admin can see everything
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        // Admin can see all tickets in their accounts
        if ($user->hasAnyPermission(['admin.read', 'tickets.view.all'])) {
            return true;
        }
        
        // Manager can see all tickets in managed accounts
        if ($user->hasAnyPermission(['tickets.view.account', 'teams.manage'])) {
            return true;
        }
        
        // User can see tickets they created or are assigned to
        return $this->created_by_id === $user->id || 
               $this->assigned_to_id === $user->id ||
               $this->assignedUsers()->where('users.id', $user->id)->exists();
    }
    
    /**
     * Check if user can edit this ticket
     */
    public function canBeEditedBy(User $user): bool
    {
        if (!$this->canBeViewedBy($user)) {
            return false;
        }
        
        // Super admin can edit everything
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        // Admin and managers can edit any ticket
        if ($user->hasAnyPermission(['admin.write', 'tickets.edit.all', 'teams.manage'])) {
            return true;
        }
        
        // Users can edit tickets they created (unless closed)
        return $this->created_by_id === $user->id && 
               !in_array($this->status, [self::STATUS_CLOSED, self::STATUS_CANCELLED]);
    }
    
    /**
     * Scopes for common queries
     */
    
    public function scopeForAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }
    
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_WAITING_CUSTOMER]);
    }
    
    public function scopeAssignedTo($query, User $user)
    {
        return $query->where('assigned_to_id', $user->id);
    }
    
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED, self::STATUS_CANCELLED]);
    }
    
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }
    
    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = static::generateTicketNumber();
            }
        });
    }
}