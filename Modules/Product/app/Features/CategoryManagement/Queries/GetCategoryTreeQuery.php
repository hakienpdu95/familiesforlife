<?php

namespace Modules\Product\Features\CategoryManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class GetCategoryTreeQuery implements QueryInterface
{
    public function __construct(
        public readonly bool $activeOnly = false,
    ) {}
}
