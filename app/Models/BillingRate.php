<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingRate extends Model
{
    /** @use HasFactory<\Database\Factories\BillingRateFactory> */
    use HasFactory, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'rate',
        'description',
        'is_active',
        'user_id',
        'account_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the billing rate.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the account that owns the billing rate.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
