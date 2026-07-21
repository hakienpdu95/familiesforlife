<?php

namespace Modules\ContentBrief\Features\BriefManagement\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class BriefOutlineItemData extends Data
{
    public function __construct(
        #[Required, Max(200)]
        public readonly string $heading,
        public readonly int $level = 2,
        public readonly ?string $notes = null,
    ) {}
}
