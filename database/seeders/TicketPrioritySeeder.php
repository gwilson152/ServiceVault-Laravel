<?php

namespace Database\Seeders;

use App\Models\TicketPriority;
use Illuminate\Database\Seeder;

class TicketPrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priorities = [
            [
                'key' => 'low',
                'name' => 'Low',
                'description' => 'Non-critical issues that can be addressed when time permits',
                'color' => '#10B981',
                'bg_color' => '#F0FDF4',
                'level' => 1,
                'severity_level' => 1,
                'escalation_multiplier' => 2.00,
                'escalation_hours' => 168, // 7 days
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'key' => 'normal',
                'name' => 'Normal',
                'description' => 'Standard priority for most issues',
                'color' => '#3B82F6',
                'bg_color' => '#EBF8FF',
                'level' => 2,
                'severity_level' => 2,
                'escalation_multiplier' => 1.00,
                'escalation_hours' => 72, // 3 days
                'sort_order' => 2,
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'key' => 'high',
                'name' => 'High',
                'description' => 'Important issues that should be addressed promptly',
                'color' => '#F59E0B',
                'bg_color' => '#FFFBEB',
                'level' => 3,
                'severity_level' => 3,
                'escalation_multiplier' => 0.75,
                'escalation_hours' => 24, // 1 day
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'key' => 'urgent',
                'name' => 'Urgent',
                'description' => 'Critical issues requiring immediate attention',
                'color' => '#EF4444',
                'bg_color' => '#FEF2F2',
                'level' => 4,
                'severity_level' => 4,
                'escalation_multiplier' => 0.50,
                'escalation_hours' => 4, // 4 hours
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'key' => 'emergency',
                'name' => 'Emergency',
                'description' => 'System down or security issues requiring immediate response',
                'color' => '#DC2626',
                'bg_color' => '#FEF2F2',
                'level' => 5,
                'severity_level' => 5,
                'escalation_multiplier' => 0.25,
                'escalation_hours' => 1, // 1 hour
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($priorities as $priorityData) {
            TicketPriority::updateOrCreate(
                ['key' => $priorityData['key']],
                $priorityData
            );
        }

        if ($this->command) {
            $this->command->info('Created '.count($priorities).' ticket priorities');
        }
    }
}