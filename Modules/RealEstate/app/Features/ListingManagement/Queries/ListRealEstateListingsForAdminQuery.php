<?php

namespace Modules\RealEstate\Features\ListingManagement\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\RealEstate\Enums\ListingType;

class ListRealEstateListingsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?ListingType $listingType = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
