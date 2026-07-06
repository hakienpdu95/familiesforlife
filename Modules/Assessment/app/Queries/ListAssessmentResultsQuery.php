<?php

namespace Modules\Assessment\Queries;

class ListAssessmentResultsQuery
{
    public function __construct(
        public readonly string  $assessmentCode,
        public readonly int     $page       = 1,
        public readonly int     $perPage    = 25,
        public readonly ?string $bandCode   = null,
        public readonly ?float  $scoreMin   = null,
        public readonly ?float  $scoreMax   = null,
        public readonly ?string $sortField  = 'calculated_at',
        public readonly string  $sortDir    = 'desc',
    ) {}
}
