<?php

namespace Modules\Post\Features\TagManagement\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class TagData extends Data
{
    public function __construct(
        #[Required, Max(120)]
        public readonly string $name,
    ) {}
}
