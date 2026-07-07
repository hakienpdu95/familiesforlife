<?php

namespace Modules\Post\Features\CategoryManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class GetCategoryTreeQuery implements QueryInterface
{
    public function __construct(
        public readonly bool $activeOnly = false,
    ) {}
}
