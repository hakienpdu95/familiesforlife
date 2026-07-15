<?php

namespace Modules\Ocop\Features\OcopCategoryManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListOcopCategoriesForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
    ) {}
}
