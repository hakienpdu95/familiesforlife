<?php

namespace Modules\Report\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class ReportServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Report';
    protected string $nameLower = 'report';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
