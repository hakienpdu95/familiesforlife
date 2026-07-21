<?php

namespace Modules\Page\Features\PageManagement\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/**
 * Validate thật nằm ở PageAdminController::validated() (cùng pattern MenuItemData/
 * MenuItemAdminController) — DTO này chỉ hydrate dữ liệu đã qua validate.
 *
 * KHÔNG chứa `status`/`is_system`/`published_at` — đây là field do Action riêng
 * (PublishPageAction/UnpublishPageAction) hoặc seeder quản lý, không đi qua form tạo/sửa
 * thường (spec §3.3 — published_at tự set bởi Action, không tin client).
 */
class PageData extends Data
{
    public function __construct(
        #[Required, Max(200)]
        public readonly string $title,

        public readonly ?string $slug = null,
        public readonly string $template = 'default',
        public readonly ?string $content = null,
        public readonly ?string $excerpt = null,
        public readonly ?string $seo_title = null,
        public readonly ?string $seo_description = null,
        public readonly bool $seo_noindex = false,
        public readonly int $sort_order = 0,
    ) {}
}
