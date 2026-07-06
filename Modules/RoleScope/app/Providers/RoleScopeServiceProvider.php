<?php

namespace Modules\RoleScope\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\RoleScope\Models\UserRoleScope;
use Modules\RoleScope\Policies\UserRoleScopePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class RoleScopeServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'RoleScope';

    protected string $nameLower = 'rolescope';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(UserRoleScope::class, UserRoleScopePolicy::class);
    }
}
