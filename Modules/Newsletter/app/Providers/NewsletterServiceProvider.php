<?php

namespace Modules\Newsletter\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Newsletter\Models\NewsletterBroadcastLog;
use Modules\Newsletter\Models\NewsletterSubscriber;
use Modules\Newsletter\Policies\NewsletterPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class NewsletterServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Newsletter';
    protected string $nameLower = 'newsletter';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // spec/Newsletter_Technical_Specification.md §11 — cùng 1 Policy đăng ký cho 2 model,
        // đúng pattern PostArticlePolicy (Gate::policy(PostArticle::class, ...) +
        // Gate::policy(PostArticleTranslation::class, ...)) — viewAny/removeSubscriber authorize
        // qua NewsletterSubscriber, sendBroadcast authorize qua NewsletterBroadcastLog.
        Gate::policy(NewsletterSubscriber::class, NewsletterPolicy::class);
        Gate::policy(NewsletterBroadcastLog::class, NewsletterPolicy::class);
    }
}
