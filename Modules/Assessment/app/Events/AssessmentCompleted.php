<?php

namespace Modules\Assessment\Events;

use Modules\Assessment\Contracts\ScoringSubjectInterface;
use Modules\Assessment\Engine\ScoringResult;
use Modules\Assessment\Models\AssessmentResult;
use Modules\WorkflowAutomation\Contracts\ProvidesWorkflowContext;

class AssessmentCompleted implements ProvidesWorkflowContext
{
    public function __construct(
        public readonly AssessmentResult        $result,
        public readonly ScoringResult           $scoringResult,
        public readonly ScoringSubjectInterface $subject,
    ) {}

    public function workflowSubject(): ?object
    {
        return $this->result;
    }

    public function workflowContext(): array
    {
        return [
            'assessment_code' => $this->result->assessment_code,
            'band_code'       => $this->result->maturity_level,
            'overall_score'   => $this->result->overall_score,
            'subject_type'    => $this->result->subject_type,
            'subject_id'      => $this->result->subject_id,
        ];
    }
}
