<?php

namespace Modules\Lead\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreTagData extends Data
{
    public function __construct(
        #[Required, StringType, Max(50)]
        public readonly string $name,

        #[Required, StringType, Max(16)]
        public readonly string $color,
    ) {}
}
