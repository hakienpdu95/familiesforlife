<?php

namespace Modules\Post\Enums;

enum SponsorLabel: string
{
    case Sponsored        = 'sponsored';
    case SponsoredNews    = 'sponsored_news';
    case BrandPartnership = 'brand_partnership';
    case Advertorial      = 'advertorial';

    // Hard-code có chủ đích, nhất quán với 7 enum label() khác trong Modules/Post (không module
    // nào trong codebase có lang/) — nếu sau này toàn bộ 8 enum chuyển sang trans(), enum này
    // cập nhật ĐỒNG LOẠT cùng lúc, không sửa lẻ tẻ riêng SponsorLabel khi có tính năng mới
    // (spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §4).
    public function label(): string
    {
        return match ($this) {
            self::Sponsored        => 'Sponsored',
            self::SponsoredNews    => 'Tin tài trợ',
            self::BrandPartnership => 'Hợp tác thương hiệu',
            self::Advertorial      => 'Advertorial',
        };
    }

    public function badgeClass(): string
    {
        return 'badge-warning'; // 1 màu thống nhất — không cần phân biệt màu theo loại nhãn
    }
}
