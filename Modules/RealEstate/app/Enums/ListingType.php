<?php

namespace Modules\RealEstate\Enums;

/** spec/RealEstateForSale_Technical_Specification.md §0/§4.2 — discriminator chính của bảng real_estate_listings. */
enum ListingType: string
{
    case Sale = 'sale';
    case Rent = 'rent';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Bán',
            self::Rent => 'Thuê',
        };
    }
}
