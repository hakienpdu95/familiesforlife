<?php

namespace Modules\Employee\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Employee\Models\Employee;
use Modules\Employee\Policies\EmployeePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class EmployeeServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Employee';

    protected string $nameLower = 'employee';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Employee::class, EmployeePolicy::class);
    }
}
