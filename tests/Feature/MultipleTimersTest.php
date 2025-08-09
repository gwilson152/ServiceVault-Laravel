<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BillingRate;
use App\Models\Project;
use App\Models\RoleTemplate;
use App\Models\Timer;
use App\Models\User;
use App\Services\TimerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class MultipleTimersTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected TimerService $timerService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user with account
        $account = Account::factory()->active()->create();
        $roleTemplate = RoleTemplate::factory()->employee()->default()->create();
        
        $this->user = User::factory()->create([
            'current_account_id' => $account->id,
        ]);
        
        $this->timerService = app(TimerService::class);
    }

    /**
     * Test that multiple timers can run concurrently for the same user.
     */
    public function test_user_can_run_multiple_concurrent_timers(): void
    {
        $this->actingAs($this->user);

        // Create first timer
        $response1 = $this->postJson('/api/timers', [
            'description' => 'First timer',
            'stop_others' => false,
            'device_id' => 'device1',
        ]);
        $response1->assertStatus(201);
        $timer1Id = $response1->json('data.id');

        // Create second timer
        $response2 = $this->postJson('/api/timers', [
            'description' => 'Second timer',
            'stop_others' => false,
            'device_id' => 'device2',
        ]);
        $response2->assertStatus(201);
        $timer2Id = $response2->json('data.id');

        // Create third timer
        $response3 = $this->postJson('/api/timers', [
            'description' => 'Third timer',
            'stop_others' => false,
            'device_id' => 'device3',
        ]);
        $response3->assertStatus(201);

        // Verify all three timers are running
        $response = $this->getJson('/api/timers/active/current');
        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertEquals(3, $data['totals']['count']);
        $this->assertCount(3, $data['data']);

        // Verify each timer is in running state
        $timerIds = collect($data['data'])->pluck('timer.id')->toArray();
        $this->assertContains($timer1Id, $timerIds);
        $this->assertContains($timer2Id, $timerIds);
    }

    /**
     * Test that stop_others=true still works for single timer mode.
     */
    public function test_stop_others_flag_works(): void
    {
        $this->actingAs($this->user);

        // Create first timer
        $this->postJson('/api/timers', [
            'description' => 'First timer',
            'stop_others' => false,
        ]);

        // Create second timer
        $this->postJson('/api/timers', [
            'description' => 'Second timer',
            'stop_others' => false,
        ]);

        // Verify two timers are running
        $response = $this->getJson('/api/timers/active/current');
        $this->assertEquals(2, $response->json('totals.count'));

        // Create third timer with stop_others=true
        $this->postJson('/api/timers', [
            'description' => 'Third timer - stops others',
            'stop_others' => true,
        ]);

        // Verify only one timer is now running
        $response = $this->getJson('/api/timers/active/current');
        $this->assertEquals(1, $response->json('totals.count'));
        $this->assertEquals('Third timer - stops others', $response->json('data.0.timer.description'));
    }

    /**
     * Test Redis state management with multiple timers.
     */
    public function test_redis_state_management_multiple_timers(): void
    {
        $this->actingAs($this->user);

        // Create multiple timers
        $timer1 = Timer::factory()->running()->create([
            'user_id' => $this->user->id,
            'description' => 'Timer 1',
            'device_id' => 'device1',
        ]);

        $timer2 = Timer::factory()->running()->create([
            'user_id' => $this->user->id,
            'description' => 'Timer 2',
            'device_id' => 'device2',
        ]);

        // Update Redis state
        $this->timerService->updateRedisState($timer1);
        $this->timerService->updateRedisState($timer2);

        // Retrieve state from Redis
        $redisStates = $this->timerService->getRedisState($this->user->id);
        
        $this->assertCount(2, $redisStates);
        $timerIds = collect($redisStates)->pluck('timer_id')->toArray();
        $this->assertContains($timer1->id, $timerIds);
        $this->assertContains($timer2->id, $timerIds);
    }

    /**
     * Test timer synchronization across devices.
     */
    public function test_timer_sync_across_devices(): void
    {
        $this->actingAs($this->user);

        // Create timers on different devices
        $timer1 = Timer::factory()->running()->create([
            'user_id' => $this->user->id,
            'device_id' => 'laptop',
        ]);

        $timer2 = Timer::factory()->running()->create([
            'user_id' => $this->user->id,
            'device_id' => 'mobile',
        ]);

        // Update Redis state
        $this->timerService->updateRedisState($timer1);
        $this->timerService->updateRedisState($timer2);

        // Sync from device
        $response = $this->postJson('/api/timers/sync', [
            'device_id' => 'laptop',
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals(2, count($data['timers']));
        $this->assertEquals('laptop', $data['device_id']);
        $this->assertNotNull($data['synced_at']);
    }

    /**
     * Test bulk operations work with multiple timers.
     */
    public function test_bulk_operations_multiple_timers(): void
    {
        $this->actingAs($this->user);

        // Create multiple timers
        $timer1 = Timer::factory()->running()->create([
            'user_id' => $this->user->id,
        ]);

        $timer2 = Timer::factory()->running()->create([
            'user_id' => $this->user->id,
        ]);

        $timer3 = Timer::factory()->running()->create([
            'user_id' => $this->user->id,
        ]);

        // Pause all timers using bulk operation
        $response = $this->postJson('/api/timers/bulk', [
            'timer_ids' => [$timer1->id, $timer2->id, $timer3->id],
            'action' => 'pause',
        ]);

        $response->assertStatus(200);
        $results = $response->json('results');
        
        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertEquals('paused', $result['status']);
        }

        // Verify timers are paused in database
        $timer1->refresh();
        $timer2->refresh();
        $timer3->refresh();
        
        $this->assertEquals('paused', $timer1->status);
        $this->assertEquals('paused', $timer2->status);
        $this->assertEquals('paused', $timer3->status);
    }

    /**
     * Test statistics calculation with multiple active timers.
     */
    public function test_statistics_with_multiple_timers(): void
    {
        $this->actingAs($this->user);

        // Create multiple active timers with different start times (no billing rate to avoid DB issues)
        $timer1 = Timer::factory()->running()->create([
            'user_id' => $this->user->id,
            'started_at' => now()->subHours(2),
            'billing_rate_id' => null,
            'project_id' => null,
            'task_id' => null,
        ]);

        $timer2 = Timer::factory()->running()->create([
            'user_id' => $this->user->id,
            'started_at' => now()->subHour(),
            'billing_rate_id' => null,
            'project_id' => null,
            'task_id' => null,
        ]);

        $response = $this->getJson('/api/timers/user/statistics');
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals(2, $data['active_timers']['count']);
        $this->assertGreaterThan(0, $data['active_timers']['total_running_amount']);
        $this->assertCount(2, $data['active_timers']['timers']);
    }

    /**
     * Test removing timer from Redis when stopped.
     */
    public function test_redis_cleanup_on_timer_stop(): void
    {
        $timer = Timer::factory()->running()->create([
            'user_id' => $this->user->id,
        ]);

        // Add to Redis
        $this->timerService->updateRedisState($timer);
        
        // Verify it's in Redis
        $states = $this->timerService->getRedisState($this->user->id);
        $this->assertCount(1, $states);

        // Remove from Redis
        $this->timerService->removeFromRedis($timer);

        // Verify it's removed
        $states = $this->timerService->getRedisState($this->user->id);
        $this->assertEmpty($states);
    }
}
