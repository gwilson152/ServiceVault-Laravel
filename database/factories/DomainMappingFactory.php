<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\RoleTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DomainMapping>
 */
class DomainMappingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companies = [
            'acme', 'globex', 'umbrella', 'waynetech', 'stark', 
            'oscorp', 'lexcorp', 'initech', 'hooli', 'piedpiper'
        ];
        
        $company = fake()->randomElement($companies);
        
        return [
            'domain_pattern' => fake()->randomElement([
                $company . '.com',
                '*.' . $company . '.com',
                'mail.' . $company . '.com',
                $company . '.org',
            ]),
            'account_id' => Account::factory(),
            'role_template_id' => RoleTemplate::factory(),
            'is_active' => fake()->boolean(85), // 85% chance of being active
            'priority' => fake()->numberBetween(0, 100),
            'description' => fake()->optional(0.7)->sentence(),
        ];
    }

    /**
     * Create an active domain mapping.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create a high priority domain mapping.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(80, 100),
        ]);
    }

    /**
     * Create a wildcard domain pattern.
     */
    public function wildcard(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain_pattern' => '*.' . fake()->domainName(),
        ]);
    }

    /**
     * Create an exact domain pattern.
     */
    public function exact(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain_pattern' => fake()->domainName(),
        ]);
    }
}
