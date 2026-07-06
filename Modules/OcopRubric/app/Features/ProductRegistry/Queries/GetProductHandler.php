<?php

namespace Modules\OcopRubric\Features\ProductRegistry\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\OcopRubric\Models\OcopProduct;

class GetProductHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): OcopProduct
    {
        /** @var GetProductQuery $query */
        return OcopProduct::with('productGroup')->findOrFail($query->productId);
    }
}
