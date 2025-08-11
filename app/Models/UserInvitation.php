<?php

namespace App\Models;

use App\Traits\HasUuid;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInvitation extends Model
{
    /** @use HasFactory<\Database\Factories\UserInvitationFactory> */
    use HasFactory;
    
    protected $fillable = [
        'email',
        'token',
        'invited_by_user_id',
        'account_id',
        'role_template_id',
        'invited_name',
        'message',
        'status',
        'expires_at',
        'accepted_at',
        'accepted_by_user_id',
    ];
    
    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];
    
    /**
     * The user who sent the invitation.
     */
    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }
    
    /**
     * The user who accepted the invitation.
     */
    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }
    
    /**
     * The account the user is being invited to.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
    
    /**
     * The role template that will be assigned.
     */
    public function roleTemplate()
    {
        return $this->belongsTo(RoleTemplate::class);
    }
    
    /**
     * Check if the invitation is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
    
    /**
     * Check if the invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }
    
    /**
     * Mark invitation as accepted.
     */
    public function markAsAccepted(User $user): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'accepted_by_user_id' => $user->id,
        ]);
    }
    
    /**
     * Generate a unique token for the invitation.
     */
    public static function generateToken(): string
    {
        return \Illuminate\Support\Str::random(64);
    }
}
