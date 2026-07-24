<?php

namespace Modules\RealEstate\Features\ListingManagement\Actions\Concerns;

use Modules\RealEstate\Enums\ListingType;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Features\ListingManagement\Data\RealEstateListingData;

/**
 * spec/RealEstateForSale_Technical_Specification.md §5.3 — field không thuộc property_type/
 * listing_type hiện tại PHẢI set về NULL khi lưu (không lưu rác nếu user đổi loại hình qua
 * lại) — vì là cột SQL thật, không phải strip key khỏi JSON. Dùng CHUNG cho Create + Update
 * để 2 Action không lặp lại logic rẽ nhánh này.
 */
trait BuildsListingAttributes
{
    protected function buildAttributes(RealEstateListingData $data): array
    {
        $isSale      = $data->listing_type === ListingType::Sale;
        $isRent      = $data->listing_type === ListingType::Rent;
        $isHouse     = $data->property_type === PropertyType::House;
        $isApartment = $data->property_type === PropertyType::Apartment;
        $isLand      = $data->property_type === PropertyType::Land;

        return [
            'listing_type'   => $data->listing_type,
            'property_type'  => $data->property_type,
            'title'          => $data->title,
            'description'    => $data->description,
            'address_detail' => $data->address_detail,
            'province_code'  => $data->province_code,
            'ward_code'      => $data->ward_code,
            'area'           => $this->resolveArea($data),
            'bedrooms'       => in_array($data->property_type, [PropertyType::House, PropertyType::Apartment], true) ? $data->bedrooms : null,
            'bathrooms'      => in_array($data->property_type, [PropertyType::House, PropertyType::Apartment], true) ? $data->bathrooms : null,
            'floors'         => $isHouse ? $data->floors : null,
            'interior_status' => $data->property_type !== PropertyType::Layout ? $data->interior_status : null,

            'is_price_negotiable' => $data->is_price_negotiable,

            // CHỈ sale
            'price'       => $isSale && ! $data->is_price_negotiable ? $data->price : null,
            'is_urgent'   => $isSale ? $data->is_urgent : false,
            'urgent_days' => $isSale && $data->is_urgent ? $data->urgent_days : null,

            // CHỈ rent
            'monthly_rent'          => $isRent && ! $data->is_price_negotiable ? $data->monthly_rent : null,
            'deposit'               => $isRent ? $data->deposit : null,
            'rental_period_months'  => $isRent ? $data->rental_period_months : null,

            // Đặc thù theo property_type — §0 v1.2, KHÔNG dùng JSON
            'house_subtype'     => $isSale && $isHouse ? $data->house_subtype : null,
            'apartment_subtype' => $isSale && $isApartment ? $data->apartment_subtype : null,
            'width'             => $isSale && ($isHouse || $isLand) ? $data->width : null,
            'length'            => $isSale && ($isHouse || $isLand) ? $data->length : null,
            'land_area'         => $isSale && ($isHouse || $isLand) ? $data->land_area : null,
            'usable_area'       => $isApartment ? $data->usable_area : null,
            'net_area'          => $isSale && $isApartment ? $data->net_area : null,
            'legal_status'      => $data->property_type !== PropertyType::Layout ? $data->legal_status : null,
            'direction'         => $data->property_type !== PropertyType::Layout ? $data->direction : null,
            'balcony_direction' => $isApartment ? $data->balcony_direction : null,
            'project_name'      => $isApartment ? $data->project_name : null,
            'apartment_address' => $isApartment ? $data->apartment_address : null,
            'usage_status'      => $isApartment ? $data->usage_status : null,
            'front_road_width'  => $isSale && $isLand ? $data->front_road_width : null,
            'current_rental_income' => $isSale ? $data->current_rental_income : null,
            'management_fee'        => $isApartment ? $data->management_fee : null,

            'is_featured' => $data->is_featured,
            'sort_order'  => $data->sort_order,
        ];
    }

    /**
     * §0 spec Bán — rent nhập trực tiếp (`area` = rent_usable_area gốc); sale suy ra từ
     * breakdown theo property_type (house/land → land_area, apartment → net_area ưu tiên,
     * fallback usable_area) — không mất thông tin gốc vì các cột breakdown vẫn lưu riêng.
     */
    private function resolveArea(RealEstateListingData $data): ?float
    {
        if ($data->listing_type === ListingType::Rent) {
            return $data->area;
        }

        return match ($data->property_type) {
            PropertyType::House, PropertyType::Land => $data->land_area ?? $data->area,
            PropertyType::Apartment => $data->net_area ?? $data->usable_area ?? $data->area,
            default => $data->area,
        };
    }
}
