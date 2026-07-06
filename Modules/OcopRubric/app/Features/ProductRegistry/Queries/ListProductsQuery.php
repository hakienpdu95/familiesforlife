<?php

namespace Modules\OcopRubric\Features\ProductRegistry\Queries;

use App\Shared\Contracts\QueryInterface;

class ListProductsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $status = null,
        public readonly ?int $productGroupId = null,
        public readonly ?string $search = null,
    ) {}
}
