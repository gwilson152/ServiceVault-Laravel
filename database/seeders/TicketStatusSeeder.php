<?php

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'key' => 'open',
                'name' => 'Open',
                'description' => 'Newly created ticket awaiting assignment or initial review',
                'color' => '#3B82F6',
                'type' => 'open',
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'in_progress',
                'name' => 'In Progress',
                'description' => 'Ticket is actively being worked on',
                'color' => '#F59E0B',
                'type' => 'in_progress',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ],
            [
                'key' => 'waiting_customer',
                'name' => 'Waiting for Customer',
                'description' => 'Waiting for customer response or action',
                'color' => '#8B5CF6',
                'type' => 'in_progress',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 3,
            ],
            [
                'key' => 'on_hold',
                'name' => 'On Hold',
                'description' => 'Ticket work is temporarily paused',
                'color' => '#6B7280',
                'type' => 'in_progress',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 4,
            ],
            [
                'key' => 'resolved',
                'name' => 'Resolved',
                'description' => 'Work completed, pending customer confirmation',
                'color' => '#10B981',
                'type' => 'resolved',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 5,
            ],
            [
                'key' => 'closed',
                'name' => 'Closed',
                'description' => 'Ticket completed and confirmed by customer',
                'color' => '#059669',
                'type' => 'closed',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 6,
            ],
            [
                'key' => 'cancelled',
                'name' => 'Cancelled',
                'description' => 'Ticket cancelled by customer or deemed invalid',
                'color' => '#EF4444',
                'type' => 'closed',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 7,
            ],
        ];

        foreach ($statuses as $statusData) {
            TicketStatus::updateOrCreate(
                ['key' => $statusData['key']],
                $statusData
            );
        }

        if ($this->command) {
            $this->command->info('Created '.count($statuses).' ticket statuses');
        }
    }
}
