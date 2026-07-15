<?php

namespace Modules\Banner\Features\BannerManagement\Data;

use Spatie\LaravelData\Data;

/**
 * Validate thật nằm ở BannerAdminController::validated() (cùng pattern CategoryData/
 * EventData) — DTO này chỉ hydrate dữ liệu đã qua validate. target_type nhận thẳng
 * null|'category' (đã được controller chuyển từ 'global' → null trước khi tới đây).
 */
class BannerData extends Data
{
    public function __construct(
        public readonly string $placement,
        public readonly ?string $target_type = null,
        public readonly ?string $target_value = null,
        public readonly ?string $title = null,

        public readonly ?string $image_path = null,
        public readonly ?int $image_width = null,
        public readonly ?int $image_height = null,
        public readonly ?int $image_size_bytes = null,
        public readonly ?string $alt_text = null,

        public readonly ?string $link_url = null,
        public readonly bool $open_in_new_tab = false,
        public readonly ?string $badge_label = null,

        public readonly ?string $start_date = null,
        public readonly ?string $end_date = null,
        public readonly int $sort_order = 0,
        public readonly bool $is_active = true,
    ) {}
}
