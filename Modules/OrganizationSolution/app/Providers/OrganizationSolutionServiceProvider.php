<?php

namespace Modules\OrganizationSolution\Providers;

use Modules\OrganizationSolution\Console\Commands\MigrateOrgVerticalTemplatesToOrganizationSolutionsCommand;
use Nwidart\Modules\Support\ModuleServiceProvider;

class OrganizationSolutionServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'OrganizationSolution';
    protected string $nameLower = 'organizationsolution';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrateOrgVerticalTemplatesToOrganizationSolutionsCommand::class,
            ]);
        }
    }
}
