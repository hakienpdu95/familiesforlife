<?php

namespace Modules\PerformanceReview\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

class StorePerformanceReviewData extends Data
{
    public function __construct(
        public readonly int $organization_id,

        #[Required]
        public readonly int $employee_id,

        #[Required]
        public readonly int $reviewer_id,

        #[Required]
        public readonly int $template_id,

        #[Required, StringType, Max(20)]
        public readonly string $period,

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

        // scores: array of {criteria_key, score, comment}
        public readonly ?array $scores = null,
    ) {}

    public static function rules(): array
    {
        $orgId = TenantContext::getOrganizationId();

        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'employee_id'  => ['required', 'integer', Rule::exists('employees', 'id')->where('organization_id', $orgId)],
            'reviewer_id'  => ['required', 'integer', Rule::exists('employees', 'id')->where('organization_id', $orgId)],
            'template_id'  => ['required', 'integer', Rule::exists('review_templates', 'id')->where('organization_id', $orgId)],
            'period'       => ['required', 'string', 'max:20'],
            'period_start' => ['nullable', 'date'],
            'period_end'   => ['nullable', 'date', 'after_or_equal:period_start'],
            'scores'       => ['nullable', 'array'],
            'scores.*.criteria_key' => ['required_with:scores', 'string', 'max:100'],
            'scores.*.score'        => ['required_with:scores', 'numeric', 'min:0'],
            'scores.*.comment'      => ['nullable', 'string'],
        ];
    }
}
