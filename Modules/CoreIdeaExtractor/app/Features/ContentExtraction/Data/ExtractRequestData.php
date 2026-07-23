<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class ExtractRequestData extends Data
{
    public function __construct(
        #[Required, Url]
        public readonly string $url,
        /** CSS selector đơn giản (id/class) do người dùng chỉ định để khoanh vùng main_content, VD ".detail-content", "#main-content". Null → dùng thuật toán tự động resolveContentRoot(). */
        #[Nullable, Max(255)]
        public readonly ?string $main_content_selector = null,
    ) {}
}
