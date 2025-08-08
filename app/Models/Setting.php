<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /** @use HasFactory<\Database\Factories\SettingFactory> */
    use HasFactory;
    
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];
    
    protected $casts = [
        'value' => 'json',
    ];
    
    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
    
    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, $value, string $type = 'system'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }
    
    /**
     * Get all settings of a specific type.
     */
    public static function getByType(string $type): array
    {
        return static::where('type', $type)->pluck('value', 'key')->toArray();
    }
}
