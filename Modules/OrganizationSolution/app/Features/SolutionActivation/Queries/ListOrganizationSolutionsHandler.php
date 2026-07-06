<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\OrganizationSolution\Models\OrganizationSolution;

class ListOrganizationSolutionsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListOrganizationSolutionsQuery $query */
        return OrganizationSolution::query()
            ->with(['businessSolution', 'blueprintVersion', 'owner', 'deployments' => fn ($q) => $q->limit(1)])
            ->when($query->status, fn ($q) => $q->where('status', $query->status))
            ->orderByDesc('created_at')
            ->get();
    }
}
