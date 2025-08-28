<?php

namespace App\Services;

use App\Models\Account;
use App\Models\DomainMapping;
use App\Models\Role;
use App\Models\RoleTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DomainAssignmentService
{
    /**
     * Assign user to account and role based on email domain.
     *
     * @return array Returns assignment details
     *
     * @throws \RuntimeException
     */
    public function assignUserBasedOnDomain(User $user): array
    {
        $domainMapping = DomainMapping::findMatchingDomain($user->email);

        if ($domainMapping) {
            return $this->assignUserFromDomainMapping($user, $domainMapping);
        }

        return $this->assignUserToDefaultAccount($user);
    }

    /**
     * Assign user based on domain mapping.
     *
     * @throws \RuntimeException
     */
    private function assignUserFromDomainMapping(User $user, DomainMapping $domainMapping): array
    {
        $account = $domainMapping->account;
        $roleTemplate = $domainMapping->roleTemplate;

        // If no specific role template is set, use the default employee template
        if (! $roleTemplate) {
            $roleTemplate = RoleTemplate::where('name', 'Employee')
                ->where('is_default', true)
                ->first();
        }

        if (! $roleTemplate) {
            throw new \RuntimeException('No suitable role template found for domain assignment.');
        }

        // Assign user directly to account and role template
        $user->update([
            'account_id' => $account->id,
            'role_template_id' => $roleTemplate->id,
        ]);

        Log::info('User assigned via domain mapping', [
            'user_id' => $user->id,
            'email' => $user->email,
            'domain' => $domainMapping->domain,
            'account_id' => $account->id,
            'account_name' => $account->name,
            'role_template' => $roleTemplate->name,
        ]);

        return [
            'method' => 'domain_mapping',
            'account' => $account,
            'role_template' => $roleTemplate,
            'domain_mapping' => $domainMapping,
        ];
    }

    /**
     * Assign user to default account when no domain mapping matches.
     *
     * @throws \RuntimeException
     */
    private function assignUserToDefaultAccount(User $user): array
    {
        // Get the primary account (first account created during setup)
        $defaultAccount = Account::orderBy('id')->first();

        if (! $defaultAccount) {
            throw new \RuntimeException('No accounts available. Please run system setup first.');
        }

        // Get the default employee role template
        $employeeTemplate = RoleTemplate::where('name', 'Employee')
            ->where('is_default', true)
            ->first();

        if (! $employeeTemplate) {
            throw new \RuntimeException('Default employee role template not found.');
        }

        // Assign user directly to default account and role template
        $user->update([
            'account_id' => $defaultAccount->id,
            'role_template_id' => $employeeTemplate->id,
        ]);

        Log::info('User assigned to default account', [
            'user_id' => $user->id,
            'email' => $user->email,
            'account_id' => $defaultAccount->id,
            'account_name' => $defaultAccount->name,
            'role_template' => $employeeTemplate->name,
        ]);

        return [
            'method' => 'default',
            'account' => $defaultAccount,
            'role_template' => $employeeTemplate,
            'domain_mapping' => null,
        ];
    }

    /**
     * Preview what assignment would happen for an email without actually assigning.
     */
    public function previewAssignmentForEmail(string $email): array
    {
        $domainMapping = DomainMapping::findMatchingDomain($email);

        if ($domainMapping) {
            return [
                'method' => 'domain_mapping',
                'account' => $domainMapping->account,
                'role_template' => $domainMapping->roleTemplate ?? $this->getDefaultEmployeeTemplate(),
                'domain_mapping' => $domainMapping,
                'email' => $email,
            ];
        }

        return [
            'method' => 'default',
            'account' => Account::orderBy('id')->first(),
            'role_template' => $this->getDefaultEmployeeTemplate(),
            'domain_mapping' => null,
            'email' => $email,
        ];
    }

    /**
     * Get the default employee role template.
     */
    private function getDefaultEmployeeTemplate(): ?RoleTemplate
    {
        return RoleTemplate::where('name', 'Employee')
            ->where('is_default', true)
            ->first();
    }

    /**
     * Validate that all required models exist for domain assignment.
     *
     * @return array Validation results
     */
    public function validateAssignmentRequirements(): array
    {
        $issues = [];

        // Check if accounts exist
        if (Account::count() === 0) {
            $issues[] = 'No accounts exist in the system. Run setup first.';
        }

        // Check if default employee role template exists
        $defaultEmployee = $this->getDefaultEmployeeTemplate();
        if (! $defaultEmployee) {
            $issues[] = 'Default Employee role template not found.';
        }

        // Check active domain mappings
        $activeMappings = DomainMapping::active()->count();

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'accounts_count' => Account::count(),
            'default_employee_exists' => (bool) $defaultEmployee,
            'active_domain_mappings' => $activeMappings,
        ];
    }
}
