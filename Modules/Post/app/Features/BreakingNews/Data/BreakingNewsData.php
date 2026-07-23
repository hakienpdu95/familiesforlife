<?php

namespace Modules\Post\Features\BreakingNews\Data;

use Spatie\LaravelData\Data;

/**
 * Validate thật nằm ở BreakingNewsAdminController::validated() (cùng pattern BannerData) — DTO
 * này chỉ hydrate dữ liệu đã qua validate.
 */
class BreakingNewsData extends Data
{
    public function __construct(
        public readonly int $article_id,
        public readonly ?string $headline_override = null,
        public readonly ?string $badge_label = null,
        public readonly ?string $starts_at = null,
        public readonly ?string $ends_at = null,
        public readonly int $sort_order = 0,
        public readonly bool $is_active = true,
    ) {}
}
