<?php

namespace Modules\ContentBrief\Features\BriefManagement\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class BriefCompetitorReferenceData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $url,
        public readonly ?string $notes = null,
    ) {}
}
