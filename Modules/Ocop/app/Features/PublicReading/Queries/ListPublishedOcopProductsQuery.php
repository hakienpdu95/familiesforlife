<?php

namespace Modules\Ocop\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryInterface;

class ListPublishedOcopProductsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $provinceCode = null,
        public readonly ?int $categoryId = null,
        public readonly ?string $search = null,
        public readonly int $page = 1,
        public readonly int $perPage = 12,
    ) {}
}
