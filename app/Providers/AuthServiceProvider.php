<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\DomainMapping;
use App\Models\ImportJob;
use App\Models\ImportProfile;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\Timer;
use App\Policies\AccountPolicy;
use App\Policies\DomainMappingPolicy;
use App\Policies\ImportJobPolicy;
use App\Policies\ImportProfilePolicy;
use App\Policies\TicketPolicy;
use App\Policies\TimeEntryPolicy;
use App\Policies\TimerPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Account::class => AccountPolicy::class,
        DomainMapping::class => DomainMappingPolicy::class,
        ImportJob::class => ImportJobPolicy::class,
        ImportProfile::class => ImportProfilePolicy::class,
        Ticket::class => TicketPolicy::class,
        TimeEntry::class => TimeEntryPolicy::class,
        Timer::class => TimerPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define custom gates for system permissions
        Gate::define('system.configure', function ($user) {
            return $user->hasPermission('system.configure');
        });
    }
}
