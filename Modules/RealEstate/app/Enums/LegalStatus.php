<?php

namespace Modules\RealEstate\Enums;

/**
 * spec/RealEstateForSale_Technical_Specification.md §0 — gộp 3 field WP `legal_house`/
 * `legal_apartment`/`legal_land` thành 1 cột `legal_status`; options hợp lệ đổi theo
 * property_type, validate ở Request (§5.3), không tách cột.
 */
enum LegalStatus: string
{
    case PinkBookPrivate  = 'so_hong_rieng';
    case PinkBookShared   = 'so_hong_chung';
    case PendingHandover  = 'dang_hoan_cong';
    case SalesContract    = 'hop_dong';
    case PendingBookIssue = 'dang_cho_cap_so';

    public function label(): string
    {
        return match ($this) {
            self::PinkBookPrivate  => 'Sổ hồng riêng',
            self::PinkBookShared   => 'Sổ hồng chung',
            self::PendingHandover  => 'Đang làm hoàn công',
            self::SalesContract    => 'Hợp đồng mua bán',
            self::PendingBookIssue => 'Đang chờ cấp sổ',
        };
    }

    /** @return array<int, self> Options hợp lệ theo property_type (§5.2/§5.3 spec Bán). */
    public static function validFor(PropertyType $propertyType): array
    {
        return match ($propertyType) {
            PropertyType::House     => [self::PinkBookPrivate, self::PinkBookShared, self::PendingHandover],
            PropertyType::Apartment => [self::PinkBookPrivate, self::SalesContract, self::PendingBookIssue],
            PropertyType::Land      => [self::PinkBookPrivate, self::PinkBookShared],
            PropertyType::Layout    => [],
        };
    }
}
