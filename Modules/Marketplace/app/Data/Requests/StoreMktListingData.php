<?php

namespace Modules\Marketplace\Data\Requests;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Modules\Marketplace\Enums\ListingType;
use Modules\Marketplace\Enums\WorkType;
use Modules\Marketplace\Enums\EmploymentType;
use Modules\Marketplace\Enums\ExperienceLevel;
use Modules\Marketplace\Enums\ListingVisibility;

class StoreMktListingData extends Data
{
    public function __construct(
        #[Nullable, Exists('organizations', 'id')]
        public readonly ?int $organization_id,

        #[Required, StringType, Max(300)]
        public readonly string $title,

        #[Required, StringType]
        public readonly string $description,

        public readonly ListingType $listing_type,
        public readonly WorkType $work_type,
        public readonly ExperienceLevel $experience_level,
        public readonly ListingVisibility $visibility,

        #[Nullable]
        public readonly ?EmploymentType $employment_type,

        #[Nullable, StringType, Max(200)]
        public readonly ?string $location,

        #[Nullable, StringType]
        public readonly ?string $requirements,

        #[Nullable, StringType]
        public readonly ?string $benefits,

        public readonly int $headcount = 1,

        #[Nullable]
        public readonly ?float $salary_min,

        #[Nullable]
        public readonly ?float $salary_max,

        public readonly string $salary_currency = 'VND',
        public readonly bool $salary_is_negotiable = false,
        public readonly bool $salary_is_visible = true,

        #[Nullable]
        public readonly ?float $budget_min,

        #[Nullable]
        public readonly ?float $budget_max,

        #[Nullable]
        public readonly ?int $duration_days,

        #[Nullable]
        public readonly ?int $department_id,

        #[Nullable]
        public readonly ?int $position_id,

        #[Nullable]
        public readonly ?string $expire_at,

        #[Nullable]
        public readonly ?array $tag_ids = null,
    ) {}
}
