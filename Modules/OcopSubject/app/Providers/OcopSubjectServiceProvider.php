<?php

namespace Modules\OcopSubject\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\OcopSubject\Models\OcopSubject;
use Modules\OcopSubject\Policies\OcopSubjectPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class OcopSubjectServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'OcopSubject';

    protected string $nameLower = 'ocopsubject';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(OcopSubject::class, OcopSubjectPolicy::class);
    }
}
