<?php

namespace Modules\BusinessSolution\Features\SolutionCatalogManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\BusinessSolution\Models\BusinessSolution;

class ListBusinessSolutionsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListBusinessSolutionsQuery $query */
        return BusinessSolution::query()
            ->with('vertical')
            ->when($query->verticalId, fn ($q) => $q->where('vertical_id', $query->verticalId))
            ->when($query->status, fn ($q) => $q->where('status', $query->status))
            ->when($query->visibility, fn ($q) => $q->where('visibility', $query->visibility))
            ->when($query->search, fn ($q) => $q->where('name', 'like', '%' . $query->search . '%'))
            ->orderBy('name')
            ->get();
    }
}
