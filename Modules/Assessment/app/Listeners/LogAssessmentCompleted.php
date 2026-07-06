<?php

namespace Modules\Assessment\Listeners;

use Modules\Assessment\Events\AssessmentCompleted;
use Modules\ActivityLog\Core\ActivityLogger;

class LogAssessmentCompleted
{
    public function handle(AssessmentCompleted $event): void
    {
        ActivityLogger::info('Assessment', 'assessment.completed', $event->result, [
            'assessment_code' => $event->result->assessment_code,
            'subject_type'    => $event->subject->getScoringSubjectType(),
            'subject_id'      => $event->subject->getScoringSubjectId(),
            'overall_score'   => $event->result->overall_score,
            'maturity_level'  => $event->result->maturity_level,
        ]);
    }
}
