<?php

namespace Modules\JobPosting\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\JobPosting\Events\JpJobPostCreated;
use Modules\JobPosting\Events\JpJobPostStatusChanged;
use Modules\JobPosting\Events\JpJobPostUpdated;
use Modules\JobPosting\Listeners\LogJpJobPostCreated;
use Modules\JobPosting\Listeners\LogJpJobPostStatusChanged;
use Modules\JobPosting\Listeners\LogJpJobPostUpdated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        JpJobPostCreated::class       => [LogJpJobPostCreated::class],
        JpJobPostUpdated::class       => [LogJpJobPostUpdated::class],
        JpJobPostStatusChanged::class => [LogJpJobPostStatusChanged::class],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
