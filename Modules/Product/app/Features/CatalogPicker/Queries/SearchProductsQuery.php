<?php

namespace Modules\Product\Features\CatalogPicker\Queries;

use App\Shared\Contracts\QueryInterface;

class SearchProductsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?int $categoryId = null,
        public readonly ?string $keyword = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
