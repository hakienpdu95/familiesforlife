<?php

namespace Modules\Deployment\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Deployment\Console\Commands\BackfillDeploymentRecordsCommand;
use Modules\Deployment\Models\DeploymentIssue;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Deployment\Policies\DeploymentIssuePolicy;
use Modules\Deployment\Policies\DeploymentTargetPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class DeploymentServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Deployment';

    protected string $nameLower = 'deployment';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(DeploymentTarget::class, DeploymentTargetPolicy::class);
        Gate::policy(DeploymentIssue::class, DeploymentIssuePolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                BackfillDeploymentRecordsCommand::class,
            ]);
        }
    }
}
