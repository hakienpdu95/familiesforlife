<?php

namespace Modules\PerformanceReview\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

class UpdatePerformanceReviewData extends Data
{
    public function __construct(
        #[Nullable]
        public readonly ?int $reviewer_id,

        #[Nullable]
        public readonly ?int $template_id,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $period,

        #[Nullable]
        public readonly ?string $period_start,

        #[Nullable]
        public readonly ?string $period_end,

        #[Nullable, StringType]
        public readonly ?string $strengths,

        #[Nullable, StringType]
        public readonly ?string $improvements,

        #[Nullable, StringType]
        public readonly ?string $goals_next_period,

        #[Nullable, StringType]
        public readonly ?string $employee_comment,

        // scores: array of {criteria_key, score, comment}
        public readonly ?array $scores = null,
    ) {}

    public static function rules(): array
    {
        return [
            'period_start' => ['nullable', 'date'],
            'period_end'   => ['nullable', 'date', 'after_or_equal:period_start'],
            'scores'       => ['nullable', 'array'],
            'scores.*.criteria_key' => ['required_with:scores', 'string', 'max:100'],
            'scores.*.score'        => ['required_with:scores', 'numeric', 'min:0'],
            'scores.*.comment'      => ['nullable', 'string'],
        ];
    }
}
