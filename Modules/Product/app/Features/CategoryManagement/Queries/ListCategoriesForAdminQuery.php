<?php

namespace Modules\Product\Features\CategoryManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListCategoriesForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
    ) {}
}
