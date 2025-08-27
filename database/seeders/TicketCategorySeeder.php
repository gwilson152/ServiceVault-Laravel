<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'key' => 'technical_support',
                'name' => 'Technical Support',
                'description' => 'General technical support requests and issues',
                'color' => '#3B82F6',
                'bg_color' => '#EBF8FF',
                'sla_hours' => 24, // 1 day SLA
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'key' => 'hardware_issues',
                'name' => 'Hardware Issues',
                'description' => 'Hardware-related problems and failures',
                'color' => '#EF4444',
                'bg_color' => '#FEF2F2',
                'sla_hours' => 8, // 8 hours SLA
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'key' => 'software_issues',
                'name' => 'Software Issues',
                'description' => 'Software bugs, crashes, and configuration problems',
                'color' => '#F59E0B',
                'bg_color' => '#FFFBEB',
                'sla_hours' => 16, // 16 hours SLA
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'key' => 'network_issues',
                'name' => 'Network Issues',
                'description' => 'Network connectivity and configuration issues',
                'color' => '#8B5CF6',
                'bg_color' => '#F5F3FF',
                'sla_hours' => 4, // 4 hours SLA (critical)
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'key' => 'feature_request',
                'name' => 'New Feature Request',
                'description' => 'Requests for new features or enhancements',
                'color' => '#10B981',
                'bg_color' => '#F0FDF4',
                'sla_hours' => 72, // 3 days SLA
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'key' => 'security_issue',
                'name' => 'Security Issue',
                'description' => 'Security vulnerabilities and concerns',
                'color' => '#DC2626',
                'bg_color' => '#FEF2F2',
                'sla_hours' => 2, // 2 hours SLA (urgent)
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'key' => 'general_inquiry',
                'name' => 'General Inquiry',
                'description' => 'General questions and information requests',
                'color' => '#6B7280',
                'bg_color' => '#F9FAFB',
                'sla_hours' => 48, // 2 days SLA
                'sort_order' => 7,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $categoryData) {
            TicketCategory::updateOrCreate(
                ['key' => $categoryData['key']],
                $categoryData
            );
        }

        if ($this->command) {
            $this->command->info('Created '.count($categories).' ticket categories');
        }
    }
}