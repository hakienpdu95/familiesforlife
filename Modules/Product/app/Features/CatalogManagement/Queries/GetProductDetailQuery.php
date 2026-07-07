<?php

namespace Modules\Product\Features\CatalogManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class GetProductDetailQuery implements QueryInterface
{
    public function __construct(
        public readonly int $productId,
    ) {}
}
