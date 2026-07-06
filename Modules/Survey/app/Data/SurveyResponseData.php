<?php

namespace Modules\Survey\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Input DTO cho POST /api/surveys/{slug}/submit.
 * Được tạo từ SubmitSurveyRequest đã qua static validation.
 */
class SurveyResponseData extends Data
{
    public function __construct(
        #[DataCollectionOf(SurveyAnswerData::class)]
        public readonly DataCollection $answers,
        public readonly ?string $respondent_ref = null,
        public readonly ?string $respondent_ip  = null,
    ) {}
}
