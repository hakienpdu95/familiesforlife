<?php

namespace Modules\RealEstate\Features\ListingManagement\Data;

use Modules\RealEstate\Enums\ApartmentSubtype;
use Modules\RealEstate\Enums\CompassDirection;
use Modules\RealEstate\Enums\HouseSubtype;
use Modules\RealEstate\Enums\InteriorStatus;
use Modules\RealEstate\Enums\LegalStatus;
use Modules\RealEstate\Enums\ListingType;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\UsageStatus;
use Spatie\LaravelData\Data;

/**
 * Validate thật nằm ở RealEstateListingAdminController::validated() (cùng pattern ProductData)
 * — DTO này chỉ hydrate dữ liệu đã qua validate. Field không thuộc property_type/listing_type
 * hiện tại bị Action set về NULL khi lưu (§5.3 spec Bán) — DTO vẫn nhận đủ mọi field, việc lọc
 * theo loại nằm ở Action, không ở đây.
 */
class RealEstateListingData extends Data
{
    public function __construct(
        public readonly ListingType $listing_type,
        public readonly PropertyType $property_type,
        public readonly string $title,
        public readonly ?string $slug = null,
        public readonly ?string $description = null,
        public readonly ?string $address_detail = null,
        public readonly string $province_code = '',
        public readonly string $ward_code = '',
        public readonly ?float $area = null,
        public readonly ?int $bedrooms = null,
        public readonly ?int $bathrooms = null,
        public readonly ?int $floors = null,
        public readonly ?InteriorStatus $interior_status = null,
        public readonly bool $is_price_negotiable = false,

        // CHỈ sale
        public readonly ?float $price = null,
        public readonly bool $is_urgent = false,
        public readonly ?int $urgent_days = null,

        // CHỈ rent
        public readonly ?float $monthly_rent = null,
        public readonly ?float $deposit = null,
        public readonly ?int $rental_period_months = null,

        // Field đặc thù theo property_type — cột SQL thật, KHÔNG dùng JSON (§0 v1.2 spec Bán)
        public readonly ?HouseSubtype $house_subtype = null,
        public readonly ?ApartmentSubtype $apartment_subtype = null,
        public readonly ?float $width = null,
        public readonly ?float $length = null,
        public readonly ?float $land_area = null,
        public readonly ?float $usable_area = null,
        public readonly ?float $net_area = null,
        public readonly ?LegalStatus $legal_status = null,
        public readonly ?CompassDirection $direction = null,
        public readonly ?CompassDirection $balcony_direction = null,
        public readonly ?string $project_name = null,
        public readonly ?string $apartment_address = null,
        public readonly ?UsageStatus $usage_status = null,
        public readonly ?float $front_road_width = null,
        public readonly ?float $current_rental_income = null,
        public readonly ?float $management_fee = null,

        public readonly bool $is_featured = false,
        public readonly int $sort_order = 0,

        /** @var string[] UUID media FilePondDraft (upload chưa gắn entity, §4.4 spec Bán) — reassociate khi lưu. */
        public readonly array $gallery_media_uuids = [],
    ) {}
}
