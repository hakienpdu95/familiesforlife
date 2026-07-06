<?php

namespace Modules\Lead\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreNoteData extends Data
{
    public function __construct(
        #[Required, StringType, Max(5000)]
        public readonly string $content,
    ) {}
}
