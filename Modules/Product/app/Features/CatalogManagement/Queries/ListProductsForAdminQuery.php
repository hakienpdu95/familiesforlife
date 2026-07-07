<?php

namespace Modules\Product\Features\CatalogManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListProductsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly ?string $search = null,
        public readonly ?int $categoryId = null,
        public readonly ?string $status = null,
        public readonly ?string $type = null,
    ) {}
}
