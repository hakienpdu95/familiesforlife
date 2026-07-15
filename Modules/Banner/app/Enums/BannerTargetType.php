<?php

namespace Modules\Banner\Enums;

/**
 * spec/Banner_Management_Technical_Specification.md §7.5 — 'global' (không targeting) KHÔNG
 * phải 1 case ở đây, nó là banners.target_type = NULL (xem Banner::$casts — cast enum tự trả
 * null khi cột null, không cần case riêng cho "không có targeting").
 *
 * spec/Province_Showcase_Technical_Specification.md §3.5 — thêm Province (target_value =
 * provinces.province_code), tái dùng nguyên cơ chế targeting đã có (xem Banner::forPlacement()).
 */
enum BannerTargetType: string
{
    case Category = 'category';
    case Province = 'province';

    public function label(): string
    {
        return match ($this) {
            self::Category => 'Theo danh mục bài viết',
            self::Province => 'Theo tỉnh/thành',
        };
    }
}
