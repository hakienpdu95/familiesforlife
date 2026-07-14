<?php

namespace Modules\Menu\Enums;

/**
 * spec/Menu_Navigation_Technical_Specification.md §5.1.
 */
enum MenuLinkType: string
{
    case Category = 'category'; // trỏ post_categories.id — dùng MenuItem::resolveUrl()
    case Url      = 'url';      // link tuỳ ý (nội bộ hoặc ngoài)
    case None     = 'none';     // chỉ là nhãn mở dropdown/flyout, không tự link

    public function label(): string
    {
        return match ($this) {
            self::Category => 'Danh mục bài viết',
            self::Url      => 'URL tuỳ ý',
            self::None     => 'Không liên kết — chỉ mở submenu',
        };
    }
}
