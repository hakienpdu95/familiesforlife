<?php

namespace Modules\RealEstate\Enums;

/**
 * spec/RealEstateForSale_Technical_Specification.md §4.2 — dùng chung cho cột `direction`
 * (house/apartment/land, gộp 3 field WP `direction`/`apartment_direction`/`land_direction`
 * trùng lặp) VÀ cột `balcony_direction` (subset 4/8 hướng qua balconyOptions()).
 */
enum CompassDirection: string
{
    case Northwest = 'tay_bac';
    case North     = 'bac';
    case Northeast = 'dong_bac';
    case West      = 'tay';
    case East      = 'dong';
    case Southwest = 'tay_nam';
    case South     = 'nam';
    case Southeast = 'dong_nam';

    public function label(): string
    {
        return match ($this) {
            self::Northwest => 'Tây Bắc',
            self::North     => 'Bắc',
            self::Northeast => 'Đông Bắc',
            self::West      => 'Tây',
            self::East      => 'Đông',
            self::Southwest => 'Tây Nam',
            self::South     => 'Nam',
            self::Southeast => 'Đông Nam',
        };
    }

    /** @return array<int, self> Subset đúng field gốc `balcony_direction` (dòng 271-278 PropertyForSaleMetabox.md). */
    public static function balconyOptions(): array
    {
        return [self::East, self::Southwest, self::South, self::Southeast];
    }
}
