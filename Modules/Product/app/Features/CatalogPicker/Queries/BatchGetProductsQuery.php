<?php

namespace Modules\Product\Features\CatalogPicker\Queries;

use App\Shared\Contracts\QueryInterface;

class BatchGetProductsQuery implements QueryInterface
{
    /** @param int[] $ids */
    public function __construct(
        public readonly array $ids,
    ) {}
}
