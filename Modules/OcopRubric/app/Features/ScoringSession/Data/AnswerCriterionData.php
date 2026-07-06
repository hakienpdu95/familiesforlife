<?php

namespace Modules\OcopRubric\Features\ScoringSession\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class AnswerCriterionData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $criterion_id,

        public readonly ?int $option_id = null,
        public readonly ?string $evidence_note = null,
    ) {}
}
