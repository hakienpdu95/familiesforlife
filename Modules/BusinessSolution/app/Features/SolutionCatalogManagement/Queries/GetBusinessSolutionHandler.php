<?php

namespace Modules\BusinessSolution\Features\SolutionCatalogManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\BusinessSolution\Models\BusinessSolution;

class GetBusinessSolutionHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): BusinessSolution
    {
        /** @var GetBusinessSolutionQuery $query */
        return BusinessSolution::query()
            ->with(['versions', 'tags', 'vertical'])
            ->findOrFail($query->businessSolutionId);
    }
}
