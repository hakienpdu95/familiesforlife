<?php

namespace Modules\Assessment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Assessment\Contracts\ScoringSubjectInterface;
use Modules\Assessment\Models\AssessmentResult;

class ForceRerunAssessmentAction
{
    use AsAction;

    public function __construct(
        private readonly RunAssessmentAction $runner,
    ) {}

    public function handle(ScoringSubjectInterface $subject): AssessmentResult
    {
        return $this->runner->handle($subject, force: true);
    }
}
