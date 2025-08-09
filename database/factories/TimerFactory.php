<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Models\BillingRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Timer>
 */
class TimerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['running', 'paused', 'stopped'];
        $status = fake()->randomElement($statuses);
        $startedAt = fake()->dateTimeBetween('-1 week', 'now');
        
        $data = [
            'user_id' => User::factory(),
            'project_id' => null, // Avoid factory relationships that might not exist
            'task_id' => null,
            'billing_rate_id' => null,
            'description' => fake()->sentence(),
            'status' => $status,
            'started_at' => $startedAt,
            'device_id' => fake()->optional(0.9)->uuid(),
            'is_synced' => fake()->boolean(80),
            'metadata' => [
                'client_ip' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'started_via' => fake()->randomElement(['api', 'web', 'mobile']),
            ],
        ];

        // Add paused_at if status is paused
        if ($status === 'paused') {
            $data['paused_at'] = fake()->dateTimeBetween($startedAt, 'now');
            $data['total_paused_duration'] = fake()->numberBetween(60, 3600);
        }

        // Add stopped_at if status is stopped
        if ($status === 'stopped') {
            $data['stopped_at'] = fake()->dateTimeBetween($startedAt, 'now');
        }

        return $data;
    }

    /**
     * Create a running timer.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'started_at' => fake()->dateTimeBetween('-4 hours', 'now'),
            'paused_at' => null,
            'stopped_at' => null,
        ]);
    }

    /**
     * Create a paused timer.
     */
    public function paused(): static
    {
        $startedAt = fake()->dateTimeBetween('-4 hours', '-1 hour');
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
            'started_at' => $startedAt,
            'paused_at' => fake()->dateTimeBetween($startedAt, 'now'),
            'total_paused_duration' => fake()->numberBetween(60, 1800),
            'stopped_at' => null,
        ]);
    }

    /**
     * Create a stopped timer.
     */
    public function stopped(): static
    {
        $startedAt = fake()->dateTimeBetween('-1 week', '-1 hour');
        return $this->state(fn (array $attributes) => [
            'status' => 'stopped',
            'started_at' => $startedAt,
            'stopped_at' => fake()->dateTimeBetween($startedAt, 'now'),
            'paused_at' => null,
        ]);
    }
}
