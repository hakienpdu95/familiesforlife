<?php
namespace Modules\Marketplace\Data\Requests;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class StoreApplicantExperienceData extends Data
{
    public function __construct(
        #[Required, StringType, Max(200)]
        public readonly string $company_name,

        #[Required, StringType, Max(150)]
        public readonly string $title,

        #[Nullable, StringType]
        public readonly ?string $description = null,

        public readonly int $start_month = 1,
        public readonly int $start_year = 2020,

        #[Nullable]
        public readonly ?int $end_month = null,

        #[Nullable]
        public readonly ?int $end_year = null,

        public readonly bool $is_current = false,
        public readonly int $sort_order = 0,
    ) {}
}
