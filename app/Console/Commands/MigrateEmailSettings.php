<?php

namespace App\Console\Commands;

use App\Models\EmailSystemConfig;
use App\Models\Setting;
use Illuminate\Console\Command;

class MigrateEmailSettings extends Command
{
    protected $signature = 'email:migrate-settings {--dry-run : Show what would be migrated without making changes}';
    protected $description = 'Migrate legacy email settings to new EmailSystemConfig model';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN - No changes will be made');
        }

        // Get all legacy email settings
        $legacySettings = Setting::where('key', 'like', 'email.%')->pluck('value', 'key');
        
        if ($legacySettings->isEmpty()) {
            $this->info('✅ No legacy email settings found');
            return;
        }

        $this->info("📧 Found {$legacySettings->count()} legacy email settings");

        // Get or create EmailSystemConfig
        $emailConfig = EmailSystemConfig::getConfig();
        
        // Map legacy settings to new model fields
        $mappings = [
            'email.system_active' => 'system_active',
            'email.incoming_enabled' => 'incoming_enabled',
            'email.outgoing_enabled' => 'outgoing_enabled',
            
            'email.email_provider' => 'incoming_provider',
            'email.outgoing_provider' => 'outgoing_provider',
            
            'email.imap_host' => 'incoming_host',
            'email.imap_port' => 'incoming_port',
            'email.imap_username' => 'incoming_username',
            'email.imap_password' => 'incoming_password',
            'email.imap_encryption' => 'incoming_encryption',
            'email.imap_folder' => 'incoming_folder',
            
            'email.smtp_host' => 'outgoing_host',
            'email.smtp_port' => 'outgoing_port',
            'email.smtp_username' => 'outgoing_username',
            'email.smtp_password' => 'outgoing_password',
            'email.smtp_encryption' => 'outgoing_encryption',
            
            'email.from_address' => 'from_address',
            'email.from_name' => 'from_name',
            'email.reply_to_address' => 'reply_to_address',
            
            'email.auto_create_tickets' => 'auto_create_tickets',
            'email.process_commands' => 'process_commands',
            'email.send_confirmations' => 'send_confirmations',
            'email.max_retries' => 'max_retries',
            
            // M365-specific settings stored in incoming_settings JSON
            'email.m365_tenant_id' => 'incoming_settings.tenant_id',
            'email.m365_client_id' => 'incoming_settings.client_id', 
            'email.m365_client_secret' => 'incoming_settings.client_secret',
            'email.m365_mailbox' => 'incoming_settings.mailbox',
            'email.m365_folder_id' => 'incoming_settings.folder_id',
            'email.m365_folder_name' => 'incoming_settings.folder_name',
        ];

        $updateData = [];
        $incomingSettings = [];
        
        foreach ($mappings as $legacyKey => $newField) {
            if ($legacySettings->has($legacyKey)) {
                $value = $legacySettings->get($legacyKey);
                
                // Convert string booleans to actual booleans
                if (in_array($value, ['true', 'false'])) {
                    $value = $value === 'true';
                }
                // Convert numeric strings to integers where appropriate
                elseif (in_array($newField, ['incoming_port', 'outgoing_port', 'max_retries']) && is_numeric($value)) {
                    $value = (int) $value;
                }
                
                // Handle nested JSON settings
                if (strpos($newField, 'incoming_settings.') === 0) {
                    $settingKey = str_replace('incoming_settings.', '', $newField);
                    $incomingSettings[$settingKey] = $value;
                    $this->line("  📝 {$legacyKey} → incoming_settings.{$settingKey}: " . json_encode($value));
                } else {
                    $updateData[$newField] = $value;
                    $this->line("  📝 {$legacyKey} → {$newField}: " . json_encode($value));
                }
            }
        }
        
        // Add incoming_settings as JSON if we have M365 settings
        if (!empty($incomingSettings)) {
            $updateData['incoming_settings'] = $incomingSettings;
        }

        if (empty($updateData)) {
            $this->warn('⚠️  No mappable settings found');
            return;
        }

        if (!$dryRun) {
            // Update the EmailSystemConfig
            $emailConfig->update($updateData);
            $this->info("✅ Migrated " . count($updateData) . " settings to EmailSystemConfig");
            
            // Optionally backup and remove legacy settings
            if ($this->confirm('Remove legacy email settings after migration?', false)) {
                Setting::where('key', 'like', 'email.%')->delete();
                $this->info('🗑️  Removed legacy email settings');
            }
        } else {
            $this->info("🔄 Would update " . count($updateData) . " fields in EmailSystemConfig");
        }

        $this->newLine();
        $this->info('📋 Migration Summary:');
        $this->table(
            ['Legacy Setting', 'New Field', 'Value'],
            collect($updateData)->map(function ($value, $field) use ($mappings) {
                $legacyKey = array_search($field, $mappings);
                return [$legacyKey, $field, json_encode($value)];
            })->toArray()
        );
    }
}