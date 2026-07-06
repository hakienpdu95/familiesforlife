<?php

namespace Modules\Recruitment\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreApplicationData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $candidate_id,

        #[Required]
        public readonly int $stage_id,

        #[Nullable, StringType, Max(36)]
        public readonly ?string $jp_job_post_id,

        #[Required, StringType]
        public readonly string $apply_source,

        #[Nullable]
        public readonly ?string $cover_letter,

        #[Nullable]
        public readonly ?float $expected_salary,

        #[Nullable]
        public readonly ?int $notice_period_days,

        // Mảng screening answers — mỗi phần tử: {jp_question_id, question_text, question_type, answer_text?, answer_bool?, answer_choices?, is_disqualifying?}
        public readonly ?array $answers = null,
    ) {}
}
