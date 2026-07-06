<?php

namespace Modules\Project\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Project\Models\Project;
use Modules\Project\Policies\ProjectPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ProjectServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Project';

    protected string $nameLower = 'project';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Project::class, ProjectPolicy::class);
    }
}
