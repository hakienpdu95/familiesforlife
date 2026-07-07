<?php

namespace Modules\Product\Features\CatalogManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Product\Models\Product;

class GetProductDetailHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Product
    {
        /** @var GetProductDetailQuery $query */
        return Product::with('category:id,name')->findOrFail($query->productId);
    }
}
