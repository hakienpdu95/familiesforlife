<?php

namespace Modules\RealEstate\Features\ListingManagement\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\RealEstate\Enums\ListingType;

/** Tabulator remote pagination/sort/filter cho dashboard/real-estate — xem RealEstateListingApiController. */
class ListRealEstateListingsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?ListingType $listingType = null,
        public readonly ?string $propertyType = null,
        public readonly ?string $approvalStatus = null,
        public readonly ?string $search = null,
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly string $sortField = 'created_at',
        public readonly string $sortDir = 'desc',
    ) {}
}
