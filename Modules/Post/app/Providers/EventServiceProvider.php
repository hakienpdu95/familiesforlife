<?php

namespace Modules\Post\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Post\Features\ArticleAuthoring\Events\ArticlePublished;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        ArticlePublished::class => [],
    ];

    protected static $shouldDiscoverEvents = false;
}
