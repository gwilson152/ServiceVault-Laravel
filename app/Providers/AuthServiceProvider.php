<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\DomainMapping;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\Timer;
use App\Policies\AccountPolicy;
use App\Policies\DomainMappingPolicy;
use App\Policies\TicketPolicy;
use App\Policies\TimeEntryPolicy;
use App\Policies\TimerPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
    }
}