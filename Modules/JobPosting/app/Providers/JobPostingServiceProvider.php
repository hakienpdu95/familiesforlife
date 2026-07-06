<?php

namespace Modules\JobPosting\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\JobPosting\Console\Commands\ExpireJpJobPostsCommand;
use Modules\JobPosting\Console\Commands\JpJobPostExpiryWarningCommand;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Observers\JpJobPostObserver;
use Modules\JobPosting\Policies\JpJobPostPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class JobPostingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'JobPosting';

    protected string $nameLower = 'job-posting';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(JpJobPost::class, JpJobPostPolicy::class);

        JpJobPost::observe(JpJobPostObserver::class);

        $this->commands([
            ExpireJpJobPostsCommand::class,
            JpJobPostExpiryWarningCommand::class,
        ]);
    }
}
