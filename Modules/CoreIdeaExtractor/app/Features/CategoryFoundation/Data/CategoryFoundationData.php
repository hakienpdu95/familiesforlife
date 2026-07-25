<?php

namespace Modules\CoreIdeaExtractor\Features\CategoryFoundation\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

/**
 * spec/CoreIdeaExtractor.md §12 (v1.4) — ngữ cảnh biên tập bền vững theo 1 PostCategory
 * ("Category Content Foundation"). core_focus/unique_angle/content_goals ánh xạ 3 thành phần
 * "Business Foundation Document" (core offering/UVP/goals) sang ngữ cảnh biên tập; audience/
 * constraints/style_sample giữ nguyên field ad-hoc đã có ở form batch (ExtractBatchRequestData)
 * — chỉ khác là được LƯU LẠI theo category thay vì gõ tay mỗi lần.
 */
class CategoryFoundationData extends Data
{
    public function __construct(
        #[Nullable, Max(2000)]
        public readonly ?string $core_focus = null,
        #[Nullable, Max(2000)]
        public readonly ?string $unique_angle = null,
        #[Nullable, Max(2000)]
        public readonly ?string $content_goals = null,
        #[Nullable, Max(500)]
        public readonly ?string $audience = null,
        #[Nullable, Max(500)]
        public readonly ?string $constraints = null,
        #[Nullable, Max(3000)]
        public readonly ?string $style_sample = null,
    ) {}
}
