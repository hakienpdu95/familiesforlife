<?php

namespace Modules\SolutionCatalog\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class SolutionCatalogServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'SolutionCatalog';
    protected string $nameLower = 'solutioncatalog';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
