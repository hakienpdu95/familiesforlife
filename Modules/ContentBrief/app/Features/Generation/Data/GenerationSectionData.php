<?php

namespace Modules\ContentBrief\Features\Generation\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class GenerationSectionData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $heading,
        #[Required]
        public readonly string $content_html,
        public readonly int $level = 2,
    ) {}
}
