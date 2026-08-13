<?php

namespace Modules\EntityComparison\Features\EntityTypeManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\EntityComparison\Models\EntityType;

class ListEntityTypesForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'sort_order', 'is_active', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListEntityTypesForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'sort_order';
        $sortDir = $query->sortDir === 'desc' ? 'desc' : 'asc';

        return EntityType::query()
            ->withCount(['entities', 'criteria'])
            ->when($query->search, fn ($q) => $q->where('name', 'like', '%'.$query->search.'%'))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
