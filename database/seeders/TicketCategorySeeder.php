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
                'key' => 'support',
                'name' => 'Technical Support',
                'description' => 'General technical support requests and issues',
                'color' => '#3B82F6',
                'bg_color' => '#EFF6FF',
                'icon' => 'LifebuoyIcon',
                'account_id' => null, // System category
                'is_system' => true,
                'is_active' => true,
                'is_default' => true, // This will be the default category
                'requires_approval' => false,
                'default_priority_multiplier' => 1.00,
                'default_estimated_hours' => 2,
                'sla_hours' => 24, // 1 day SLA
                'sort_order' => 1,
            ],
            [
                'key' => 'maintenance',
                'name' => 'System Maintenance',
                'description' => 'Scheduled maintenance and system updates',
                'color' => '#10B981',
                'bg_color' => '#ECFDF5',
                'icon' => 'WrenchScrewdriverIcon',
                'account_id' => null, // System category
                'is_system' => true,
                'is_active' => true,
                'is_default' => false,
                'requires_approval' => true, // Maintenance typically requires approval
                'default_priority_multiplier' => 0.75,
                'default_estimated_hours' => 4,
                'sla_hours' => 72, // 3 days SLA
                'sort_order' => 2,
            ],
            [
                'key' => 'development',
                'name' => 'Development',
                'description' => 'Custom development and feature requests',
                'color' => '#8B5CF6',
                'bg_color' => '#F3F4FF',
                'icon' => 'CodeBracketIcon',
                'account_id' => null, // System category
                'is_system' => true,
                'is_active' => true,
                'is_default' => false,
                'requires_approval' => true,
                'default_priority_multiplier' => 1.25,
                'default_estimated_hours' => 8,
                'sla_hours' => 168, // 1 week SLA
                'sort_order' => 3,
            ],
            [
                'key' => 'consulting',
                'name' => 'Consulting',
                'description' => 'Business consulting and advisory services',
                'color' => '#F59E0B',
                'bg_color' => '#FFFBEB',
                'icon' => 'UserGroupIcon',
                'account_id' => null, // System category
                'is_system' => true,
                'is_active' => true,
                'is_default' => false,
                'requires_approval' => true,
                'default_priority_multiplier' => 1.50,
                'default_estimated_hours' => 6,
                'sla_hours' => 120, // 5 days SLA
                'sort_order' => 4,
            ],
            [
                'key' => 'emergency',
                'name' => 'Emergency',
                'description' => 'Critical issues requiring immediate attention',
                'color' => '#EF4444',
                'bg_color' => '#FEF2F2',
                'icon' => 'ExclamationTriangleIcon',
                'account_id' => null, // System category
                'is_system' => true,
                'is_active' => true,
                'is_default' => false,
                'requires_approval' => false, // Emergency tickets don't wait for approval
                'default_priority_multiplier' => 2.00,
                'default_estimated_hours' => 1,
                'sla_hours' => 4, // 4 hours SLA
                'sort_order' => 5,
            ],
            [
                'key' => 'training',
                'name' => 'Training',
                'description' => 'User training and knowledge transfer',
                'color' => '#06B6D4',
                'bg_color' => '#ECFEFF',
                'icon' => 'AcademicCapIcon',
                'account_id' => null, // System category
                'is_system' => true,
                'is_active' => true,
                'is_default' => false,
                'requires_approval' => true,
                'default_priority_multiplier' => 0.50,
                'default_estimated_hours' => 3,
                'sla_hours' => 96, // 4 days SLA
                'sort_order' => 6,
            ],
            [
                'key' => 'documentation',
                'name' => 'Documentation',
                'description' => 'Documentation creation and updates',
                'color' => '#64748B',
                'bg_color' => '#F8FAFC',
                'icon' => 'DocumentTextIcon',
                'account_id' => null, // System category
                'is_system' => true,
                'is_active' => true,
                'is_default' => false,
                'requires_approval' => false,
                'default_priority_multiplier' => 0.75,
                'default_estimated_hours' => 2,
                'sla_hours' => 120, // 5 days SLA
                'sort_order' => 7,
            ],
        ];

        foreach ($categories as $categoryData) {
            TicketCategory::updateOrCreate(
                ['key' => $categoryData['key']],
                $categoryData
            );
        }
    }
}
