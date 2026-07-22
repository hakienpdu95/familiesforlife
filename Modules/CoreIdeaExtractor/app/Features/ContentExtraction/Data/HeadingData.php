<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Spatie\LaravelData\Data;

class HeadingData extends Data
{
    public function __construct(
        public readonly int $level,
        public readonly string $text,
    ) {}
}
