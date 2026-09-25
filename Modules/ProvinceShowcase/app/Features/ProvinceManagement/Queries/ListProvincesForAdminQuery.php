<?php

namespace Modules\ProvinceShowcase\Features\ProvinceManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListProvincesForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly ?string $search = null,
        public readonly ?int $regionId = null,
        public readonly ?string $placeType = null,
        public readonly ?bool $isActive = null,
        public readonly ?bool $isFeatured = null,
        public readonly string $sortField = 'province_code',
        public readonly string $sortDir = 'asc',
    ) {}
}
