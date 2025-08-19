<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default attachment settings for ticket messages
        $attachmentSettings = [
            'tickets.attachments.max_files' => 10,
            'tickets.attachments.max_file_size_kb' => 10240, // 10MB
            'tickets.attachments.total_size_limit_kb' => 51200, // 50MB
            'tickets.attachments.allowed_extensions' => [
                'pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx', 'csv',
                'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar',
            ],
        ];

        // Timer system defaults
        $timerSettings = [
            'timers.manual_time_override_enabled' => true,
            'timers.default_rounding_minutes' => 15,
            'timers.max_concurrent_timers' => 5,
        ];

        // Email notification defaults
        $emailSettings = [
            'email.notifications.ticket_created' => true,
            'email.notifications.ticket_updated' => true,
            'email.notifications.ticket_assigned' => true,
            'email.notifications.timer_committed' => false,
        ];

        // Combine all default settings
        $defaultSettings = array_merge($attachmentSettings, $timerSettings, $emailSettings);

        // Create settings if they don't exist
        foreach ($defaultSettings as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => is_array($value) ? json_encode($value) : $value,
                    'type' => is_array($value) ? 'array' : (is_bool($value) ? 'boolean' : (is_numeric($value) ? 'integer' : 'string')),
                    'description' => $this->getSettingDescription($key),
                    'is_public' => false,
                ]
            );
        }
    }

    /**
     * Get description for setting keys
     */
    private function getSettingDescription(string $key): string
    {
        $descriptions = [
            // Attachment settings
            'tickets.attachments.max_files' => 'Maximum number of files that can be uploaded per message',
            'tickets.attachments.max_file_size_kb' => 'Maximum file size in KB for individual attachments',
            'tickets.attachments.total_size_limit_kb' => 'Maximum total size in KB for all attachments per message',
            'tickets.attachments.allowed_extensions' => 'List of allowed file extensions for ticket attachments',

            // Timer settings
            'timers.manual_time_override_enabled' => 'Allow users to manually override timer duration when committing',
            'timers.default_rounding_minutes' => 'Default rounding interval in minutes for timer commits',
            'timers.max_concurrent_timers' => 'Maximum number of concurrent timers per user',

            // Email settings
            'email.notifications.ticket_created' => 'Send email notifications when new tickets are created',
            'email.notifications.ticket_updated' => 'Send email notifications when tickets are updated',
            'email.notifications.ticket_assigned' => 'Send email notifications when tickets are assigned',
            'email.notifications.timer_committed' => 'Send email notifications when timers are committed to time entries',
        ];

        return $descriptions[$key] ?? 'System setting';
    }
}
