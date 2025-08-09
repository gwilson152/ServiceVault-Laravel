<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\DomainMapping;
use App\Models\RoleTemplate;
use App\Models\User;
use App\Services\DomainAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DomainAssignmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test domain assignment service with matching domain.
     */
    public function test_assigns_user_to_correct_account_based_on_domain(): void
    {
        // Create test data
        $account = Account::factory()->active()->create(['name' => 'Test Company']);
        $roleTemplate = RoleTemplate::factory()->employee()->create();
        
        $domainMapping = DomainMapping::factory()->create([
            'domain_pattern' => 'testcompany.com',
            'account_id' => $account->id,
            'role_template_id' => $roleTemplate->id,
            'is_active' => true,
            'priority' => 10,
        ]);

        $user = User::factory()->create([
            'email' => 'john.doe@testcompany.com'
        ]);

        $service = new DomainAssignmentService();
        $result = $service->assignUserBasedOnDomain($user);

        // Assert assignment was made correctly
        $this->assertEquals('domain_mapping', $result['method']);
        $this->assertEquals($account->id, $result['account']->id);
        $this->assertEquals($roleTemplate->id, $result['role_template']->id);
        
        // Verify user was assigned to correct account
        $user->refresh();
        $this->assertEquals($account->id, $user->current_account_id);
        $this->assertTrue($user->accounts()->where('accounts.id', $account->id)->exists());
    }

    /**
     * Test wildcard domain pattern matching.
     */
    public function test_wildcard_domain_pattern_matching(): void
    {
        // Create test data
        $account = Account::factory()->active()->create(['name' => 'Umbrella Corp']);
        $roleTemplate = RoleTemplate::factory()->employee()->create();
        
        $domainMapping = DomainMapping::factory()->create([
            'domain_pattern' => '*.umbrella.com',
            'account_id' => $account->id,
            'role_template_id' => $roleTemplate->id,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'email' => 'alice@mail.umbrella.com'
        ]);

        $service = new DomainAssignmentService();
        $result = $service->assignUserBasedOnDomain($user);

        $this->assertEquals('domain_mapping', $result['method']);
        $this->assertEquals($account->id, $result['account']->id);
    }

    /**
     * Test fallback to default account when no domain matches.
     */
    public function test_assigns_to_default_account_when_no_domain_matches(): void
    {
        // Create default account and role
        $defaultAccount = Account::factory()->active()->create(['name' => 'Default Company']);
        RoleTemplate::factory()->employee()->default()->create();

        $user = User::factory()->create([
            'email' => 'john@nomatch.com'
        ]);

        $service = new DomainAssignmentService();
        $result = $service->assignUserBasedOnDomain($user);

        $this->assertEquals('default', $result['method']);
        $this->assertEquals($defaultAccount->id, $result['account']->id);
    }

    /**
     * Test preview assignment functionality.
     */
    public function test_preview_assignment_for_email(): void
    {
        $account = Account::factory()->active()->create(['name' => 'Test Company']);
        $roleTemplate = RoleTemplate::factory()->employee()->create();
        
        DomainMapping::factory()->create([
            'domain_pattern' => 'testcompany.com',
            'account_id' => $account->id,
            'role_template_id' => $roleTemplate->id,
            'is_active' => true,
        ]);

        $service = new DomainAssignmentService();
        $preview = $service->previewAssignmentForEmail('test@testcompany.com');

        $this->assertEquals('domain_mapping', $preview['method']);
        $this->assertEquals($account->id, $preview['account']->id);
        $this->assertEquals($roleTemplate->id, $preview['role_template']->id);
        $this->assertEquals('test@testcompany.com', $preview['email']);
    }

    /**
     * Test domain pattern matching method.
     */
    public function test_domain_pattern_matching(): void
    {
        $mapping = DomainMapping::factory()->create([
            'domain_pattern' => '*.example.com'
        ]);

        $this->assertTrue($mapping->matchesDomain('mail.example.com'));
        $this->assertTrue($mapping->matchesDomain('subdomain.example.com'));
        $this->assertFalse($mapping->matchesDomain('example.com'));
        $this->assertFalse($mapping->matchesDomain('other.com'));
    }
}
