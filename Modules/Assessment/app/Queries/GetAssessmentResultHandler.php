<?php

namespace Modules\Assessment\Queries;

use Modules\Assessment\Models\AssessmentResult;

class GetAssessmentResultHandler
{
    public function handle(GetAssessmentResultQuery $query): ?AssessmentResult
    {
        return AssessmentResult::forSubject($query->subjectType, $query->subjectId)
            ->with([
                'domainScores',
                'signalFlags',
                'painPoints',
                'recommendations',
                'roadmapPhases.phase.milestones',
                'classification',
                'questionScores',
            ])
            ->first();
    }
}
