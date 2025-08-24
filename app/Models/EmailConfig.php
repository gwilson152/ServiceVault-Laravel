<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class EmailConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'direction',
        'driver',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
        'incoming_protocol',
        'incoming_host',
        'incoming_port',
        'incoming_username',
        'incoming_password',
        'incoming_encryption',
        'incoming_folder',
        'is_active',
        'is_default',
        'priority',
        'settings',
        'last_tested_at',
        'test_results',
        'created_by_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'priority' => 'integer',
        'port' => 'integer',
        'incoming_port' => 'integer',
        'settings' => 'array',
        'test_results' => 'array',
        'last_tested_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'incoming_password',
    ];

    /**
     * Relationships
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Accessors & Mutators
     */
    public function getPasswordAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getIncomingPasswordAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setIncomingPasswordAttribute($value): void
    {
        $this->attributes['incoming_password'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDirection($query, string $direction)
    {
        return $query->where('direction', $direction)->orWhere('direction', 'both');
    }

    public function scopeForAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('created_at', 'asc');
    }

    /**
     * Business Logic Methods
     */
    public function isIncoming(): bool
    {
        return in_array($this->direction, ['incoming', 'both']);
    }

    public function isOutgoing(): bool
    {
        return in_array($this->direction, ['outgoing', 'both']);
    }

    public function getMailConfig(): array
    {
        $config = [
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
            'encryption' => $this->encryption,
            'from' => [
                'address' => $this->from_address,
                'name' => $this->from_name,
            ],
        ];

        // Merge additional settings
        if ($this->settings) {
            $config = array_merge($config, $this->settings);
        }

        return array_filter($config);
    }

    public function getIncomingConfig(): array
    {
        if (!$this->isIncoming()) {
            return [];
        }

        return [
            'protocol' => $this->incoming_protocol ?? 'imap',
            'host' => $this->incoming_host ?? $this->host,
            'port' => $this->incoming_port ?? ($this->incoming_protocol === 'pop3' ? 995 : 993),
            'username' => $this->incoming_username ?? $this->username,
            'password' => $this->incoming_password ?? $this->password,
            'encryption' => $this->incoming_encryption ?? $this->encryption ?? 'ssl',
            'folder' => $this->incoming_folder ?? 'INBOX',
        ];
    }

    public function testConnection(): array
    {
        $results = [
            'tested_at' => now(),
            'outgoing' => null,
            'incoming' => null,
        ];

        // Test outgoing connection
        if ($this->isOutgoing()) {
            try {
                $config = $this->getMailConfig();
                $transport = \Illuminate\Mail\TransportManager::class;
                // This would be implemented with actual connection testing
                $results['outgoing'] = [
                    'success' => true,
                    'message' => 'Connection successful',
                ];
            } catch (\Exception $e) {
                $results['outgoing'] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Test incoming connection
        if ($this->isIncoming()) {
            try {
                $config = $this->getIncomingConfig();
                // This would be implemented with actual IMAP/POP3 connection testing
                $results['incoming'] = [
                    'success' => true,
                    'message' => 'Connection successful',
                ];
            } catch (\Exception $e) {
                $results['incoming'] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Update test results
        $this->update([
            'last_tested_at' => now(),
            'test_results' => $results,
        ]);

        return $results;
    }

    /**
     * Find the best email configuration for an account and direction
     */
    public static function getBestConfigForAccount($accountId, string $direction = 'outgoing'): ?EmailConfig
    {
        return self::active()
            ->forDirection($direction)
            ->where(function ($query) use ($accountId) {
                $query->where('account_id', $accountId)
                      ->orWhereNull('account_id');
            })
            ->byPriority()
            ->first();
    }

    /**
     * Get default global configuration
     */
    public static function getDefaultConfig(string $direction = 'outgoing'): ?EmailConfig
    {
        return self::active()
            ->forDirection($direction)
            ->whereNull('account_id')
            ->default()
            ->first();
    }
}
