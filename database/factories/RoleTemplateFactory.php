<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RoleTemplate>
 */
class RoleTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roleNames = ['Employee', 'Manager', 'Administrator', 'Supervisor', 'Analyst', 'Developer'];

        return [
            'name' => fake()->randomElement($roleNames),
            'permissions' => [
                'view_dashboard',
                'manage_timers',
                'view_reports',
            ],
            'is_system_role' => fake()->boolean(30),
            'is_default' => false,
            'description' => fake()->optional(0.8)->sentence(),
        ];
    }

    /**
     * Create a default role template.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Create an employee role template.
     */
    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Employee',
            'permissions' => [
                'view_dashboard',
                'manage_timers',
                'view_own_reports',
            ],
        ]);
    }

    /**
     * Create a system role template.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system_role' => true,
            'permissions' => [
                'view_dashboard',
                'manage_system',
                'manage_users',
                'manage_accounts',
            ],
        ]);
    }
}
