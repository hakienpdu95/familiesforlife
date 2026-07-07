<?php

namespace Modules\Post\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostCategory;
use Modules\Post\Policies\PostArticlePolicy;
use Modules\Post\Policies\PostCategoryPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PostServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Post';
    protected string $nameLower = 'post';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(PostCategory::class, PostCategoryPolicy::class);
        Gate::policy(PostArticle::class, PostArticlePolicy::class);
    }
}
