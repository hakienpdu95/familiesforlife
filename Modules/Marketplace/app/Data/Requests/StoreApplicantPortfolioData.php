<?php
namespace Modules\Marketplace\Data\Requests;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;

class StoreApplicantPortfolioData extends Data
{
    public function __construct(
        #[Required, StringType, Max(200)]
        public readonly string $title,

        #[Nullable, StringType]
        public readonly ?string $description = null,

        #[Nullable, StringType, Max(300)]
        public readonly ?string $project_url = null,

        #[Nullable, StringType]
        public readonly ?string $thumbnail_url = null,

        #[Nullable, StringType, Max(300)]
        public readonly ?string $tech_stack = null,

        #[Nullable]
        public readonly ?int $completed_year = null,

        public readonly int $sort_order = 0,
    ) {}
}
