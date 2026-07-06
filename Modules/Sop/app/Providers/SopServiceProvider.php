<?php

namespace Modules\Sop\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Sop\Console\Commands\ArchiveExpiredSopCommand;
use Modules\Sop\Console\Commands\SopExpiryWarningCommand;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Policies\SopPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SopServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Sop';

    protected string $nameLower = 'sop';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(SopProcess::class, SopPolicy::class);

        $this->commands([
            ArchiveExpiredSopCommand::class,
            SopExpiryWarningCommand::class,
        ]);
    }
}
