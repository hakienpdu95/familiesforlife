<?php

namespace Modules\Assessment\Events;

use Modules\Assessment\Contracts\ScoringSubjectInterface;

class AssessmentFailed
{
    public function __construct(
        public readonly ScoringSubjectInterface $subject,
        public readonly \Throwable              $exception,
    ) {}
}
