<?php

namespace Modules\AccessTrade\Features\TopProductManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListTopProductsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $merchant = null,
        public readonly ?string $brand = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $sortField = 'total',
        public readonly string $sortDir = 'desc',
    ) {}
}
