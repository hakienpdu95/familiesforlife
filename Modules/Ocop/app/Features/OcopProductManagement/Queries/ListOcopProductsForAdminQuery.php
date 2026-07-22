<?php

namespace Modules\Ocop\Features\OcopProductManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListOcopProductsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?int $categoryId = null,
        public readonly ?string $status = null,
        public readonly ?string $provinceCode = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $sortField = 'created_at',
        public readonly string $sortDir = 'desc',
    ) {}
}
