<?php

namespace Modules\Video\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Video\Models\Video;
use Modules\Video\Policies\VideoPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class VideoServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Video';
    protected string $nameLower = 'video';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Video::class, VideoPolicy::class);
    }
}
