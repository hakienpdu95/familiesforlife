<?php

namespace Modules\Assessment\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class AssessmentServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Assessment';
    protected string $nameLower = 'assessment';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
