<?php

namespace Modules\Post\Features\ArticleAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class TranslationData extends Data
{
    public function __construct(
        #[Required, Max(300)]
        public readonly string $title,

        public readonly ?string $slug = null, // null → auto-generate từ title (CreateTranslationAction/UpdateTranslationAction)
        public readonly ?string $excerpt = null,
        public readonly ?string $seo_title = null,
        public readonly ?string $seo_description = null,

        /** @var array<int, array> Dãy block-composer — cùng shape với ArticleData::$blocks. */
        public readonly array $blocks = [],
    ) {}
}
