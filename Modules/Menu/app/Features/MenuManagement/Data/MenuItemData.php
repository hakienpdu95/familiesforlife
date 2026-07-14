<?php

namespace Modules\Menu\Features\MenuManagement\Data;

use Modules\Menu\Enums\MenuLinkType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/**
 * Validate thật nằm ở MenuItemAdminController::validated() (cùng pattern CategoryData/
 * CategoryAdminController) — DTO này chỉ hydrate dữ liệu đã qua validate.
 */
class MenuItemData extends Data
{
    public function __construct(
        #[Required, Max(150)]
        public readonly string $label,

        public readonly string $location = 'header',
        public readonly ?int $parent_id = null,
        public readonly ?string $icon = null,
        public readonly int $sort_order = 0,
        public readonly bool $is_active = true,
        public readonly bool $open_in_new_tab = false,

        public readonly MenuLinkType $link_type = MenuLinkType::None,
        public readonly ?int $category_id = null,
        public readonly ?string $url = null,
    ) {}
}
