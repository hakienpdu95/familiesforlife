<?php

namespace Modules\Marketplace\Data\Requests;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Modules\Marketplace\Enums\ReviewRelationType;

class StoreReviewData extends Data
{
    public function __construct(
        #[Required, Between(1, 5)]
        public readonly int $overall_rating,

        public readonly ReviewRelationType $relation_type = ReviewRelationType::Hired,

        #[Nullable, StringType, Max(200)]
        public readonly ?string $title = null,

        #[Nullable, StringType]
        public readonly ?string $content = null,

        #[Nullable, Between(1, 5)]
        public readonly ?int $rating_quality = null,

        #[Nullable, Between(1, 5)]
        public readonly ?int $rating_communication = null,

        #[Nullable, Between(1, 5)]
        public readonly ?int $rating_punctuality = null,
    ) {}
}
