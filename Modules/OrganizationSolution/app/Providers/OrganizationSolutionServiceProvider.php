<?php

namespace Modules\OrganizationSolution\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class OrganizationSolutionServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'OrganizationSolution';
    protected string $nameLower = 'organizationsolution';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
