<?php

namespace Modules\ContentOutlines\Features\OutlineGeneration\Data;

use Spatie\LaravelData\Data;

/**
 * spec/ContentOutlines_Technical_Specification.md §3.1 — DTO input dùng chung cho
 * BuildContentOutlinePromptAction, CreateContentOutlineAction, RegenerateContentOutlinePromptAction.
 * Validate thật nằm ở Store/UpdateContentOutlineRequest::rules() (§7.1) — DTO này chỉ hydrate dữ
 * liệu đã qua validate (cùng pattern N8nConnectionData/BannerData).
 */
class ContentOutlineInputData extends Data
{
    public function __construct(
        public readonly ?string $label,
        public readonly string $topic,
        public readonly string $target_keyword,
        public readonly ?string $secondary_keywords = null,
        public readonly ?string $search_intent = null,
        public readonly ?int $post_category_id = null,
        public readonly ?string $target_audience = null,
        public readonly ?string $content_goal = null,
        public readonly ?string $tone_style = null,
        public readonly ?string $competitor_urls = null,
        public readonly ?int $desired_word_count = null,
        public readonly string $language = 'vi',
        public readonly string $outline_depth = 'standard', // §4.1 (v1.1) — brief|standard|detailed
        public readonly ?string $content_role = null, // §4.9 (v1.6) — pillar|cluster|null (chưa xác định)
        public readonly ?string $additional_notes = null,
    ) {}
}
