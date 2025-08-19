<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a preference value for a user by key.
     */
    public static function get(string $userId, string $key, $default = null)
    {
        $preference = static::where('user_id', $userId)
            ->where('key', $key)
            ->first();

        return $preference ? $preference->value : $default;
    }

    /**
     * Set a preference value for a user.
     */
    public static function set(string $userId, string $key, $value): void
    {
        static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Remove a preference for a user.
     */
    public static function remove(string $userId, string $key): void
    {
        static::where('user_id', $userId)
            ->where('key', $key)
            ->delete();
    }
}
