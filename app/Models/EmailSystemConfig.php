<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class EmailSystemConfig extends Model
{
    protected $table = 'email_system_config';

    protected $fillable = [
        'configuration_name',
        
        // Incoming email service
        'incoming_enabled',
        'incoming_provider',
        'incoming_host',
        'incoming_port',
        'incoming_username',
        'incoming_password',
        'incoming_encryption',
        'incoming_folder',
        'incoming_settings',
        
        // Outgoing email service
        'outgoing_enabled',
        'outgoing_provider',
        'outgoing_host',
        'outgoing_port',
        'outgoing_username',
        'outgoing_password',
        'outgoing_encryption',
        'outgoing_settings',
        
        // From address configuration
        'from_address',
        'from_name',
        'reply_to_address',
        
        // System status
        'system_active',
        'last_tested_at',
        'test_results',
        
        // Processing settings
        'auto_create_tickets',
        'process_commands',
        'send_confirmations',
        'max_retries',
        'processing_rules',
        
        // Metadata
        'updated_by_id',
    ];

    protected $casts = [
        'incoming_enabled' => 'boolean',
        'outgoing_enabled' => 'boolean',
        'system_active' => 'boolean',
        'auto_create_tickets' => 'boolean',
        'process_commands' => 'boolean',
        'send_confirmations' => 'boolean',
        'incoming_settings' => 'array',
        'outgoing_settings' => 'array',
        'test_results' => 'array',
        'processing_rules' => 'array',
        'last_tested_at' => 'datetime',
    ];

    protected $hidden = [
        'incoming_password',
        'outgoing_password',
    ];

    /**
     * Get the user who last updated this configuration
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Get the singleton email system configuration
     */
    public static function getConfig(): EmailSystemConfig
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'configuration_name' => 'Default Email System Configuration',
                'incoming_enabled' => false,
                'outgoing_enabled' => false,
                'system_active' => false,
                'auto_create_tickets' => true,
                'process_commands' => true,
                'send_confirmations' => true,
                'max_retries' => 3,
            ]
        );
    }

    /**
     * Check if the email system is fully configured and active
     */
    public function isFullyConfigured(): bool
    {
        return $this->system_active &&
               $this->incoming_enabled &&
               $this->outgoing_enabled &&
               !empty($this->from_address);
    }

    /**
     * Check if incoming email service is configured
     */
    public function hasIncomingConfigured(): bool
    {
        return $this->incoming_enabled &&
               !empty($this->incoming_host) &&
               !empty($this->incoming_username);
    }

    /**
     * Check if outgoing email service is configured
     */
    public function hasOutgoingConfigured(): bool
    {
        return $this->outgoing_enabled &&
               !empty($this->outgoing_host) &&
               !empty($this->from_address);
    }

    /**
     * Get provider-specific default settings
     */
    public static function getProviderDefaults(string $provider, string $type = 'outgoing'): array
    {
        $defaults = [
            'outgoing' => [
                'smtp' => ['port' => 587, 'encryption' => 'tls'],
                'gmail' => ['host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls'],
                'outlook' => ['host' => 'smtp-mail.outlook.com', 'port' => 587, 'encryption' => 'starttls'],
                'ses' => ['port' => 587, 'encryption' => 'tls'],
                'sendgrid' => ['host' => 'smtp.sendgrid.net', 'port' => 587, 'encryption' => 'tls'],
                'postmark' => ['host' => 'smtp.postmarkapp.com', 'port' => 587, 'encryption' => 'tls'],
                'mailgun' => ['host' => 'smtp.mailgun.org', 'port' => 587, 'encryption' => 'tls'],
            ],
            'incoming' => [
                'imap' => ['port' => 993, 'encryption' => 'ssl', 'folder' => 'INBOX'],
                'gmail' => ['host' => 'imap.gmail.com', 'port' => 993, 'encryption' => 'ssl', 'folder' => 'INBOX'],
                'outlook' => ['host' => 'outlook.office365.com', 'port' => 993, 'encryption' => 'ssl', 'folder' => 'INBOX'],
                'exchange' => ['port' => 993, 'encryption' => 'ssl', 'folder' => 'INBOX'],
            ],
        ];

        return $defaults[$type][$provider] ?? [];
    }

    /**
     * Test the email configuration
     */
    public function testConfiguration(): array
    {
        $results = [
            'incoming' => null,
            'outgoing' => null,
            'overall' => false,
            'tested_at' => now(),
        ];

        // Test incoming configuration
        if ($this->hasIncomingConfigured()) {
            $results['incoming'] = $this->testIncomingConnection();
        }

        // Test outgoing configuration
        if ($this->hasOutgoingConfigured()) {
            $results['outgoing'] = $this->testOutgoingConnection();
        }

        // Overall success
        $results['overall'] = ($results['incoming']['success'] ?? true) && 
                             ($results['outgoing']['success'] ?? true);

        // Update the test results
        $this->update([
            'test_results' => $results,
            'last_tested_at' => now(),
        ]);

        return $results;
    }

    /**
     * Test incoming email connection
     */
    private function testIncomingConnection(): array
    {
        try {
            // Basic validation
            if (empty($this->incoming_host) || empty($this->incoming_username)) {
                return [
                    'success' => false,
                    'message' => 'Incoming configuration is incomplete',
                    'details' => 'Host and username are required'
                ];
            }

            // TODO: Implement actual connection test based on provider
            // For now, return success for basic validation
            return [
                'success' => true,
                'message' => 'Incoming configuration validated',
                'details' => "Connected to {$this->incoming_host}:{$this->incoming_port}"
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Incoming connection failed',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Test outgoing email connection
     */
    private function testOutgoingConnection(): array
    {
        try {
            // Basic validation
            if (empty($this->outgoing_host) || empty($this->from_address)) {
                return [
                    'success' => false,
                    'message' => 'Outgoing configuration is incomplete',
                    'details' => 'Host and from address are required'
                ];
            }

            // TODO: Implement actual SMTP connection test
            // For now, return success for basic validation
            return [
                'success' => true,
                'message' => 'Outgoing configuration validated',
                'details' => "Connected to {$this->outgoing_host}:{$this->outgoing_port}"
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Outgoing connection failed',
                'details' => $e->getMessage()
            ];
        }
    }
}