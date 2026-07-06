<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Queries;

use App\Shared\Contracts\QueryInterface;

class ListOrganizationSolutionsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $status = null,
    ) {}
}
