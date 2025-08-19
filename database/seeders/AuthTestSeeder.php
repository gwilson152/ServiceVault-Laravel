<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Role;
use App\Models\RoleTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthTestSeeder extends Seeder
{
    public function run(): void
    {
        // Create test account
        $testAccount = Account::create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'settings' => json_encode([
                'timezone' => 'UTC',
                'currency' => 'USD',
            ]),
        ]);

        // Create role templates
        $adminTemplate = RoleTemplate::create([
            'name' => 'System Administrator',
            'permissions' => ['system.manage', 'accounts.create', 'users.manage'],
            'is_system_role' => true,
        ]);

        $employeeTemplate = RoleTemplate::create([
            'name' => 'Employee',
            'permissions' => ['timers.create', 'timers.manage', 'time_entries.create'],
            'is_system_role' => false,
        ]);

        // Create test users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $employee = User::create([
            'name' => 'Employee User',
            'email' => 'employee@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create and assign roles
        $adminRole = Role::create([
            'account_id' => $testAccount->id,
            'role_template_id' => $adminTemplate->id,
        ]);

        $employeeRole = Role::create([
            'account_id' => $testAccount->id,
            'role_template_id' => $employeeTemplate->id,
        ]);

        // Assign roles to users
        $admin->roles()->attach($adminRole->id);
        $employee->roles()->attach($employeeRole->id);

        // Assign users to account
        $testAccount->users()->attach([$admin->id, $employee->id]);

        $this->command->info('Created test users:');
        $this->command->info('Admin: admin@test.com / password');
        $this->command->info('Employee: employee@test.com / password');
    }
}
