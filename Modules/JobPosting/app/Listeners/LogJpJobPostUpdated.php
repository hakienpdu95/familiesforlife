<?php

namespace Modules\JobPosting\Listeners;

use Modules\JobPosting\Events\JpJobPostUpdated;

class LogJpJobPostUpdated
{
    public function handle(JpJobPostUpdated $event): void
    {
        activity()
            ->on($event->jobPost)
            ->log('job_post.updated');
    }
}
