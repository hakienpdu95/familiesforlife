<?php

namespace Modules\AccessTrade\Providers;

use Modules\AccessTrade\Console\Commands\SyncAccessTradeCommand;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AccessTradeServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'AccessTrade';
    protected string $nameLower = 'accesstrade';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->commands([
            SyncAccessTradeCommand::class,
        ]);
    }
}
