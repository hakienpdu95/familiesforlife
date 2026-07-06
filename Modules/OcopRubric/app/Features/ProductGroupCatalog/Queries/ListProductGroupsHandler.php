<?php

namespace Modules\OcopRubric\Features\ProductGroupCatalog\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\OcopRubric\Models\OcopProductGroup;

class ListProductGroupsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListProductGroupsQuery $query */
        return OcopProductGroup::query()
            ->with('activeRubricVersion')
            ->when($query->activeOnly, fn ($q) => $q->where('is_active', true))
            ->when($query->industryCode, fn ($q) => $q->where('industry_code', $query->industryCode))
            ->when($query->search, fn ($q) => $q->where('name', 'like', '%' . $query->search . '%'))
            ->orderBy('industry_code')
            ->orderBy('sort_order')
            ->get();
    }
}
