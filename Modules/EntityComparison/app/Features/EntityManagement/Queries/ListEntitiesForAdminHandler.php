<?php

namespace Modules\EntityComparison\Features\EntityManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\EntityComparison\Models\Entity;

class ListEntitiesForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'sort_order', 'is_active', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListEntitiesForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return Entity::query()
            ->with('entityType:id,name')
            ->when($query->search, fn ($q) => $q->where('name', 'like', "%{$query->search}%"))
            ->when($query->entityTypeId, fn ($q) => $q->where('entity_type_id', $query->entityTypeId))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
