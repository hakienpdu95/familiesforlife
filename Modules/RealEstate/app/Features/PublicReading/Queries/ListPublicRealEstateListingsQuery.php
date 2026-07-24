<?php

namespace Modules\RealEstate\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\RealEstate\Enums\ListingType;

class ListPublicRealEstateListingsQuery implements QueryInterface
{
    public function __construct(
        public readonly ListingType $listingType,
        public readonly int $page = 1,
        public readonly int $perPage = 12,
        public readonly ?string $propertyType = null,
        public readonly ?string $provinceCode = null,
        public readonly ?float $priceMin = null,
        public readonly ?float $priceMax = null,
        public readonly ?int $bedrooms = null,
        public readonly ?float $areaMin = null,
        public readonly ?float $areaMax = null,
    ) {}
}
