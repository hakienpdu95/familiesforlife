<?php

namespace Modules\BusinessBlueprint\Providers;

use Modules\BusinessBlueprint\Console\Commands\MigrateVerticalTemplatesToBlueprintsCommand;
use Nwidart\Modules\Support\ModuleServiceProvider;

class BusinessBlueprintServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'BusinessBlueprint';
    protected string $nameLower = 'businessblueprint';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrateVerticalTemplatesToBlueprintsCommand::class,
            ]);
        }
    }
}
