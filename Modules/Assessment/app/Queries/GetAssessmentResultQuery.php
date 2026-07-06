<?php

namespace Modules\Assessment\Queries;

class GetAssessmentResultQuery
{
    public function __construct(
        public readonly string $subjectType,
        public readonly int    $subjectId,
    ) {}
}
