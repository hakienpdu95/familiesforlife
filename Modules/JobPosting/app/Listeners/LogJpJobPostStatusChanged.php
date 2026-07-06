<?php

namespace Modules\JobPosting\Listeners;

use Modules\JobPosting\Events\JpJobPostStatusChanged;

class LogJpJobPostStatusChanged
{
    public function handle(JpJobPostStatusChanged $event): void
    {
        activity()
            ->on($event->jobPost)
            ->withProperties([
                'old_status' => $event->oldStatus->value,
                'new_status' => $event->newStatus->value,
            ])
            ->log('job_post.status_changed');
    }
}
