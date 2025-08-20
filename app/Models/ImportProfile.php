<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class ImportProfile extends Model
{
    /** @use HasFactory<\Database\Factories\ImportProfileFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'type',
        'host',
        'port',
        'database',
        'username',
        'password',
        'ssl_mode',
        'description',
        'connection_options',
        'is_active',
        'created_by',
        'last_tested_at',
        'last_test_result',
    ];

    protected $casts = [
        'connection_options' => 'array',
        'last_test_result' => 'array',
        'is_active' => 'boolean',
        'port' => 'integer',
        'last_tested_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the user who created this import profile.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the import jobs for this profile.
     */
    public function importJobs(): HasMany
    {
        return $this->hasMany(ImportJob::class, 'profile_id');
    }

    /**
     * Get the import mappings for this profile.
     */
    public function importMappings(): HasMany
    {
        return $this->hasMany(ImportMapping::class, 'profile_id');
    }

    /**
     * Encrypt the password when setting it.
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt the password when getting it.
     */
    public function getPasswordAttribute($value)
    {
        if ($value) {
            return Crypt::decryptString($value);
        }
        return null;
    }

    /**
     * Get the connection configuration array.
     */
    public function getConnectionConfig(): array
    {
        return [
            'driver' => 'pgsql',
            'url' => null,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => $this->ssl_mode,
            'options' => array_merge([
                \PDO::ATTR_TIMEOUT => 30,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ], $this->connection_options ?? []),
        ];
    }

    /**
     * Check if this profile has been tested recently.
     */
    public function isTestedRecently(): bool
    {
        return $this->last_tested_at && $this->last_tested_at->isAfter(now()->subHours(24));
    }

    /**
     * Check if the last connection test was successful.
     */
    public function lastTestSuccessful(): bool
    {
        return $this->last_test_result && 
               isset($this->last_test_result['success']) && 
               $this->last_test_result['success'] === true;
    }

    /**
     * Get the field mappings for this import profile.
     */
    public function mappings()
    {
        return $this->hasMany(ImportMapping::class, 'profile_id');
    }
}
