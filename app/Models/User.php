<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_account_id',
        'preferences',
        'timezone',
        'locale',
        'last_active_at',
        'is_active',
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
            'is_active' => 'boolean',
        ];
    }

    /**
     * Service Vault Relationships
     */

    /**
     * The current account the user is working in.
     */
    public function currentAccount()
    {
        return $this->belongsTo(Account::class, 'current_account_id');
    }

    /**
     * All accounts the user has access to (many-to-many).
     */
    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_user');
    }

    /**
     * All roles assigned to this user across all accounts.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
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
     * Projects the user is assigned to.
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user');
    }

    /**
     * ABAC Permission Methods
     */

    /**
     * Check if user has a specific permission for an account.
     */
    public function hasPermissionForAccount(string $permission, Account $account): bool
    {
        return app(PermissionService::class)->userHasPermissionForAccount($this, $permission, $account);
    }

    /**
     * Check if user has a system-level permission.
     */
    public function hasSystemPermission(string $permission): bool
    {
        return app(PermissionService::class)->userHasSystemPermission($this, $permission);
    }

    /**
     * Get user's roles for a specific account.
     */
    public function getRolesForAccount(Account $account)
    {
        return $this->roles()->where('account_id', $account->id)->get();
    }

    /**
     * Get all permissions for the user in the current account context.
     */
    public function getAllPermissions(): array
    {
        if (!$this->currentAccount) {
            return [];
        }

        return app(PermissionService::class)->getUserPermissionsForAccount($this, $this->currentAccount);
    }

    /**
     * Set the user's current working account.
     */
    public function switchToAccount(Account $account): void
    {
        // Verify user has access to this account
        if (!$this->accounts->contains($account)) {
            throw new \InvalidArgumentException('User does not have access to this account');
        }

        $this->update(['current_account_id' => $account->id]);
        $this->refresh();
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
