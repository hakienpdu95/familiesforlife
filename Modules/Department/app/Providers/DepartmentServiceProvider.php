<?php

namespace Modules\Department\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Department\Models\Department;
use Modules\Department\Policies\DepartmentPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class DepartmentServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Department';

    protected string $nameLower = 'department';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Department::class, DepartmentPolicy::class);
    }
}
