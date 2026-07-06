<?php

namespace Modules\JobTitle\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\JobTitle\Events\JobTitleCreated;

class LogJobTitleCreated
{
    public function handle(JobTitleCreated $event): void
    {
        ActivityLogger::info('JobTitle', 'job_title_created', $event->jobTitle, [
            'job_title_id'    => $event->jobTitle->id,
            'name'            => $event->jobTitle->name,
            'code'            => $event->jobTitle->code,
            'organization_id' => $event->jobTitle->organization_id,
        ]);
    }
}
