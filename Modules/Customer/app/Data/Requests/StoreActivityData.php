<?php

namespace Modules\Customer\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Integer;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreActivityData extends Data
{
    public function __construct(
        #[Required, Integer]
        public readonly int     $type,

        #[Required, StringType, Max(500)]
        public readonly string  $title,

        #[Nullable, StringType, Max(5000)]
        public readonly ?string $description      = null,

        #[Nullable, StringType, Max(2000)]
        public readonly ?string $outcome          = null,

        #[Nullable, Date]
        public readonly ?string $scheduled_at     = null,

        #[Nullable, Date]
        public readonly ?string $completed_at     = null,

        #[Nullable, Integer, Min(1)]
        public readonly ?int    $duration_minutes = null,
    ) {}
}
