<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class ExtractRequestData extends Data
{
    public function __construct(
        #[Required, Url]
        public readonly string $url,
    ) {}
}
