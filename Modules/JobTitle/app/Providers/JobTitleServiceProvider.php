<?php

namespace Modules\JobTitle\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\JobTitle\Models\JobTitle;
use Modules\JobTitle\Policies\JobTitlePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class JobTitleServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'JobTitle';

    protected string $nameLower = 'jobtitle';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(JobTitle::class, JobTitlePolicy::class);
    }
}
