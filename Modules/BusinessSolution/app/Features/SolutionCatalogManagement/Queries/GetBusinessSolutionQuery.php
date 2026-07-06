<?php

namespace Modules\BusinessSolution\Features\SolutionCatalogManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class GetBusinessSolutionQuery implements QueryInterface
{
    public function __construct(
        public readonly int $businessSolutionId,
    ) {}
}
