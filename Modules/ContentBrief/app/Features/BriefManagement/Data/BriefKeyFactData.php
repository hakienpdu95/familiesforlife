<?php

namespace Modules\ContentBrief\Features\BriefManagement\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class BriefKeyFactData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $fact,
        public readonly ?string $source_url = null,
    ) {}
}
