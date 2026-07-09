<?php

namespace Modules\BusinessBlueprint\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class BusinessBlueprintServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'BusinessBlueprint';
    protected string $nameLower = 'businessblueprint';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
