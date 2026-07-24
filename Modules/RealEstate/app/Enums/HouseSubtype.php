<?php

namespace Modules\RealEstate\Enums;

/** spec/RealEstateForSale_Technical_Specification.md §4.2 — chỉ property_type=house + listing_type=sale. */
enum HouseSubtype: string
{
    case Alley    = 'alley';
    case Street   = 'street';
    case Adjacent = 'adjacent';
    case Villa    = 'villa';

    public function label(): string
    {
        return match ($this) {
            self::Alley    => 'Nhà hẻm',
            self::Street   => 'Nhà mặt tiền',
            self::Adjacent => 'Nhà liền kề',
            self::Villa    => 'Biệt thự',
        };
    }
}
