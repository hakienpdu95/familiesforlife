<?php

namespace Modules\OcopRubric\Features\ProductRegistry\Queries;

use App\Shared\Contracts\QueryInterface;

class GetProductQuery implements QueryInterface
{
    public function __construct(public readonly int $productId) {}
}
