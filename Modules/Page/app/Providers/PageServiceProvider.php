<?php

namespace Modules\Page\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Page\Models\Page;
use Modules\Page\Policies\PagePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PageServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Page';
    protected string $nameLower = 'page';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Page::class, PagePolicy::class);
    }
}
