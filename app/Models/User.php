<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\PermissionService;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'account_id',
        'role_template_id',
        'preferences',
        'timezone',
        'locale',
        'last_active_at',
        'last_login_at',
        'is_active',
        'is_visible',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            'last_active_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * Service Vault Relationships
     */

    /**
     * The account the user belongs to.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * The role template assigned to this user.
     */
    public function roleTemplate()
    {
        return $this->belongsTo(RoleTemplate::class);
    }

    
    /**
     * Check if user has a specific permission through their role template
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->roleTemplate) {
            return false;
        }

        // Super Admin always has all permissions
        if ($this->roleTemplate->isSuperAdmin()) {
            return true;
        }
        
        return in_array($permission, $this->roleTemplate->getAllPermissions());
    }
    
    /**
     * Check if user is Super Admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->roleTemplate && $this->roleTemplate->isSuperAdmin();
    }
    
    /**
     * Check if user has any of the specified permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Timers created by this user.
     */
    public function timers()
    {
        return $this->hasMany(Timer::class);
    }

    /**
     * Time entries created by this user.
     */
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }
    
    /**
     * Tickets assigned to this user (as agent).
     */
    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'agent_id');
    }
    
    /**
     * Tickets created by this user.
     */
    public function createdTickets()
    {
        return $this->hasMany(Ticket::class, 'created_by_id');
    }
    
    /**
     * Tickets where this user is the customer.
     */
    public function customerTickets()
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }


    /**
     * ABAC Permission Methods
     */

    /**
     * Check if user has a specific permission for their account.
     */
    public function hasPermissionForAccount(string $permission, Account $account): bool
    {
        // Users can only access their own account
        if (!$this->account || $this->account->id !== $account->id) {
            return false;
        }

        return $this->hasPermission($permission);
    }

    /**
     * Check if user has a system-level permission.
     */
    public function hasSystemPermission(string $permission): bool
    {
        return $this->hasPermission($permission);
    }

    /**
     * Get all permissions for the user.
     */
    public function getAllPermissions(): array
    {
        if (!$this->roleTemplate) {
            return [];
        }

        return $this->roleTemplate->getAllPermissions();
    }

    /**
     * Get user's current active timer (if any).
     */
    public function getCurrentTimer()
    {
        return $this->timers()
                   ->whereIn('status', ['running', 'paused'])
                   ->orderBy('updated_at', 'desc')
                   ->first();
    }

    /**
     * Update user's last activity timestamp.
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_active_at' => now()]);
    }
}
