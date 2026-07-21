<?php

namespace Modules\ContentBrief\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\ContentBrief\Models\ContentBrief;
use Modules\ContentBrief\Policies\ContentBriefPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ContentBriefServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'ContentBrief';
    protected string $nameLower = 'contentbrief';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(ContentBrief::class, ContentBriefPolicy::class);
    }
}
