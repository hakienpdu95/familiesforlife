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

        /**
         * spec/Media_Library_Technical_Specification.md §8 — UUID media FilePond (collection
         * `banner`) chờ gắn vào banner vừa tạo — CHỈ dùng ở luồng tạo mới. Form sửa gắn ảnh
         * thẳng qua context header.
         */
        public readonly ?string $cover_media_uuid = null,
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
