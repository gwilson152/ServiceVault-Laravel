<?php

namespace Database\Seeders;

use App\Models\TicketPriority;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'description' => 'Low priority ticket with flexible timeline',
                'color' => '#6B7280',
                'bg_color' => '#F3F4F6',
                'icon' => 'ChevronDownIcon',
                'is_active' => true,
                'is_default' => false,
                'severity_level' => 1,
                'escalation_multiplier' => 1.50, // 50% longer SLA
                'sort_order' => 1
            ],
            [
                'key' => 'normal',
                'name' => 'Normal',
                'description' => 'Standard priority ticket with normal timeline',
                'color' => '#3B82F6',
                'bg_color' => '#DBEAFE',
                'icon' => 'MinusIcon',
                'is_active' => true,
                'is_default' => true,
                'severity_level' => 2,
                'escalation_multiplier' => 1.00, // Normal SLA
                'sort_order' => 2
            ],
            [
                'key' => 'medium',
                'name' => 'Medium',
                'description' => 'Above normal priority requiring prompt attention',
                'color' => '#F59E0B',
                'bg_color' => '#FEF3C7',
                'icon' => 'ChevronUpIcon',
                'is_active' => true,
                'is_default' => false,
                'severity_level' => 3,
                'escalation_multiplier' => 0.75, // 25% faster SLA
                'sort_order' => 3
            ],
            [
                'key' => 'high',
                'name' => 'High',
                'description' => 'High priority ticket requiring urgent attention',
                'color' => '#EF4444',
                'bg_color' => '#FEE2E2',
                'icon' => 'ChevronDoubleUpIcon',
                'is_active' => true,
                'is_default' => false,
                'severity_level' => 4,
                'escalation_multiplier' => 0.50, // 50% faster SLA
                'sort_order' => 4
            ],
            [
                'key' => 'urgent',
                'name' => 'Urgent',
                'description' => 'Critical issue requiring immediate attention',
                'color' => '#DC2626',
                'bg_color' => '#FEE2E2',
                'icon' => 'ExclamationTriangleIcon',
                'is_active' => true,
                'is_default' => false,
                'severity_level' => 5,
                'escalation_multiplier' => 0.25, // 75% faster SLA
                'sort_order' => 5
            ]
        ];

        foreach ($priorities as $priorityData) {
            TicketPriority::updateOrCreate(
                ['key' => $priorityData['key']], 
                $priorityData
            );
        }

        $this->command->info('Created ' . count($priorities) . ' ticket priorities');
    }
}
