<?php

namespace Modules\Aicem\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Aicem\Listeners\SuggestExampleGoodFromPublishedArticle;
use Modules\Post\Features\ArticleAuthoring\Events\ArticlePublished;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        ArticlePublished::class => [
            SuggestExampleGoodFromPublishedArticle::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;
}
