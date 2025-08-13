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
        'user_type',
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
     * User Type Constants - Service Vault Architecture
     */
    public const USER_TYPE_AGENT = 'agent';
    public const USER_TYPE_ACCOUNT_USER = 'account_user';

    /**
     * User Type Methods
     */

    /**
     * Check if user is an Agent (service provider who can log time entries).
     */
    public function isAgent(): bool
    {
        return $this->user_type === self::USER_TYPE_AGENT;
    }

    /**
     * Check if user is an Account User (customer who submits tickets but cannot log time).
     */
    public function isAccountUser(): bool
    {
        return $this->user_type === self::USER_TYPE_ACCOUNT_USER;
    }

    /**
     * Check if user can create time entries.
     * Only Agents are allowed to log time in the Service Vault architecture.
     */
    public function canCreateTimeEntries(): bool
    {
        return $this->isAgent();
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
     * Get accounts the user has access to (for now, just their primary account)
     * Returns an Eloquent relationship that can be chained
     */
    public function accounts()
    {
        // For now, return just the user's primary account as a queryable relationship
        // Use the Account model's query builder with proper table aliasing
        return Account::query()->where('accounts.id', $this->account_id ?? '00000000-0000-0000-0000-000000000000');
    }

    /**
     * Get accessible account IDs for queries
     */
    public function getAccessibleAccountIds(?string $permission = null): array
    {
        if (!$this->account_id) {
            return [];
        }

        // For now, just return the user's primary account ID
        // This can be expanded later for hierarchical access
        return [$this->account_id];
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
