<?php

namespace Modules\RealEstate\Enums;

/**
 * spec/RealEstateForSale_Technical_Specification.md §4.2 — chỉ `property_type=apartment`.
 * spec/RealEstateForRent_Technical_Specification.md §2.2 — với rent, option `RentedOut` vô
 * nghĩa (không thuê được nhà đang có người thuê khác) — Request giới hạn subset khi rent.
 */
enum UsageStatus: string
{
    case Living   = 'dang_o';
    case RentedOut = 'dang_cho_thue';
    case Vacant   = 'nha_trong';

    public function label(): string
    {
        return match ($this) {
            self::Living    => 'Đang ở',
            self::RentedOut => 'Đang cho thuê',
            self::Vacant    => 'Nhà trống',
        };
    }

    /** @return array<int, self> Options hợp lệ theo listing_type (§2.2 spec Thuê — loại bỏ RentedOut khi rent). */
    public static function validFor(ListingType $listingType): array
    {
        return match ($listingType) {
            ListingType::Sale => [self::Living, self::RentedOut, self::Vacant],
            ListingType::Rent => [self::Living, self::Vacant],
        };
    }
}
