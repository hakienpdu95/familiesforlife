<?php

namespace Modules\Branch\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Branch\Models\Branch;
use Modules\Branch\Policies\BranchPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class BranchServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Branch';

    protected string $nameLower = 'branch';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Branch::class, BranchPolicy::class);
    }
}
