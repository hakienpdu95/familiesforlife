<?php

namespace Modules\RealEstate\Enums;

/**
 * spec/RealEstateForSale_Technical_Specification.md §4.2 — `land` chỉ hợp lệ khi
 * listing_type=sale; `layout` chỉ hợp lệ khi listing_type=rent; `house`/`apartment` dùng
 * chung cả 2 (spec/RealEstateForRent_Technical_Specification.md §0).
 */
enum PropertyType: string
{
    case House     = 'house';
    case Apartment = 'apartment';
    case Land      = 'land';
    case Layout    = 'layout';

    public function label(): string
    {
        return match ($this) {
            self::House     => 'Nhà riêng',
            self::Apartment => 'Căn hộ chung cư',
            self::Land      => 'Đất thổ cư',
            self::Layout    => 'Mặt bằng',
        };
    }

    /** @return array<int, self> Loại hình hợp lệ theo listing_type — validate ở Request. */
    public static function validFor(ListingType $listingType): array
    {
        return match ($listingType) {
            ListingType::Sale => [self::House, self::Apartment, self::Land],
            ListingType::Rent => [self::House, self::Apartment, self::Layout],
        };
    }
}
