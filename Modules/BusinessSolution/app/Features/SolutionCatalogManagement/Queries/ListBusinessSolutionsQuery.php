<?php

namespace Modules\BusinessSolution\Features\SolutionCatalogManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListBusinessSolutionsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?int    $verticalId = null,
        public readonly ?string $status     = null,
        public readonly ?string $visibility = null,
        public readonly ?string $search     = null,
    ) {}
}
