<?php

namespace Modules\JobPosting\Listeners;

use Modules\JobPosting\Events\JpJobPostCreated;

class LogJpJobPostCreated
{
    public function handle(JpJobPostCreated $event): void
    {
        activity()
            ->on($event->jobPost)
            ->log('job_post.created');
    }
}
