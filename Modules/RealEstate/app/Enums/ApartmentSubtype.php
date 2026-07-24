<?php

namespace Modules\RealEstate\Enums;

/** spec/RealEstateForSale_Technical_Specification.md §4.2 — chỉ property_type=apartment + listing_type=sale. */
enum ApartmentSubtype: string
{
    case Apartment = 'apartment';
    case Officetel = 'officetel';
    case Duplex    = 'duplex';
    case Penthouse = 'penthouse';
    case Shophouse = 'shophouse';

    public function label(): string
    {
        return match ($this) {
            self::Apartment => 'Căn hộ',
            self::Officetel => 'Officetel',
            self::Duplex    => 'Duplex',
            self::Penthouse => 'Penthouse',
            self::Shophouse => 'Shophouse',
        };
    }
}
