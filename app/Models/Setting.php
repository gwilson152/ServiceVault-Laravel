<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /** @use HasFactory<\Database\Factories\SettingFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'key',
        'value',
        'type',
        'account_id',
        'user_id',
        'description',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    // Relationships
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeSystem($query)
    {
        return $query->where('type', 'system');
    }

    public function scopeAccount($query, $accountId = null)
    {
        return $query->where('type', 'account')
                     ->where('account_id', $accountId);
    }

    public function scopeUser($query, $userId = null)
    {
        return $query->where('type', 'user')
                     ->where('user_id', $userId);
    }

    /**
     * Get a setting value by key (system level only).
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)
                         ->where('type', 'system')
                         ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Get a setting value with account/user context.
     */
    public static function getValueWithContext(string $key, ?string $accountId = null, ?string $userId = null, $default = null)
    {
        $setting = static::where('key', $key)
                         ->where('type', $accountId ? 'account' : ($userId ? 'user' : 'system'))
                         ->when($accountId, fn($q) => $q->where('account_id', $accountId))
                         ->when($userId, fn($q) => $q->where('user_id', $userId))
                         ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, $value, string $type = 'system', ?string $accountId = null, ?string $userId = null): void
    {
        static::updateOrCreate(
            [
                'key' => $key,
                'type' => $type,
                'account_id' => $accountId,
                'user_id' => $userId,
            ],
            ['value' => $value]
        );
    }

    /**
     * Get all settings of a specific type.
     */
    public static function getByType(string $type, ?string $accountId = null, ?string $userId = null): array
    {
        return static::where('type', $type)
                     ->when($accountId, fn($q) => $q->where('account_id', $accountId))
                     ->when($userId, fn($q) => $q->where('user_id', $userId))
                     ->pluck('value', 'key')
                     ->toArray();
    }
}
