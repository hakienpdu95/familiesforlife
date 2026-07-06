<?php

namespace Modules\BusinessSolution\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class BusinessSolutionServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'BusinessSolution';
    protected string $nameLower = 'businesssolution';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
