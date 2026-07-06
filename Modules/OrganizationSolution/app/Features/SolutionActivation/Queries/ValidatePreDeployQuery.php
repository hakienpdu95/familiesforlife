<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Queries;

use App\Shared\Contracts\QueryInterface;

class ValidatePreDeployQuery implements QueryInterface
{
    public function __construct(
        public readonly int $organizationSolutionId,
    ) {}
}
