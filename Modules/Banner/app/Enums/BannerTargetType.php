<?php

namespace Modules\Banner\Enums;

/**
 * spec/Banner_Management_Technical_Specification.md §7.5 — 'global' (không targeting) KHÔNG
 * phải 1 case ở đây, nó là banners.target_type = NULL (xem Banner::$casts — cast enum tự trả
 * null khi cột null, không cần case riêng cho "không có targeting").
 */
enum BannerTargetType: string
{
    case Category = 'category';

    public function label(): string
    {
        return match ($this) {
            self::Category => 'Theo danh mục bài viết',
        };
    }
}
