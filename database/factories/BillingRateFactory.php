<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Account;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BillingRate>
 */
class BillingRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rates = [25.00, 35.00, 50.00, 75.00, 100.00, 125.00, 150.00, 200.00];
        $rateNames = [
            'Junior Developer', 'Senior Developer', 'Lead Developer', 
            'Project Manager', 'Consultant', 'Specialist', 'Expert',
            'Standard Rate', 'Premium Rate', 'Emergency Rate'
        ];
        
        return [
            'name' => fake()->randomElement($rateNames),
            'rate' => fake()->randomElement($rates),
            'user_id' => null,
            'account_id' => null, 
            'project_id' => null,
            'is_default' => fake()->boolean(10), // 10% chance of being default
            'is_active' => fake()->boolean(90),
            'description' => fake()->optional(0.6)->sentence(),
        ];
    }

    /**
     * Create a standard rate.
     */
    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Standard Rate',
            'rate' => 50.00,
            'is_default' => true,
        ]);
    }

    /**
     * Create a premium rate.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Premium Rate',
            'rate' => 100.00,
        ]);
    }

    /**
     * Create a user-specific rate.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'account_id' => null,
            'project_id' => null,
        ]);
    }

    /**
     * Create an account-specific rate.
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'account_id' => $account->id,
            'project_id' => null,
        ]);
    }
}
