<?php

namespace Modules\Leave\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Leave\Models\LeavePolicy;
use Modules\Leave\Models\LeaveRequest;
use Modules\Leave\Policies\LeavePolicyPolicy;
use Modules\Leave\Policies\LeaveRequestPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class LeaveServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Leave';

    protected string $nameLower = 'leave';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(LeavePolicy::class, LeavePolicyPolicy::class);
        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);
    }
}
