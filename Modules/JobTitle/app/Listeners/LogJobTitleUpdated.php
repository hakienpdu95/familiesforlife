<?php

namespace Modules\JobTitle\Listeners;

use Modules\ActivityLog\Core\ActivityLogger;
use Modules\JobTitle\Events\JobTitleUpdated;

class LogJobTitleUpdated
{
    public function handle(JobTitleUpdated $event): void
    {
        ActivityLogger::info('JobTitle', 'job_title_updated', $event->jobTitle, [
            'job_title_id'    => $event->jobTitle->id,
            'name'            => $event->jobTitle->name,
            'code'            => $event->jobTitle->code,
            'organization_id' => $event->jobTitle->organization_id,
        ]);
    }
}
