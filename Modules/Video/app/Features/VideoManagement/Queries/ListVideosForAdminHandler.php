<?php

namespace Modules\Video\Features\VideoManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Video\Models\Video;

class ListVideosForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'sort_order', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListVideosForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'sort_order';
        $sortDir = $query->sortDir === 'desc' ? 'desc' : 'asc';

        return Video::query()
            ->when($query->search, fn ($q) => $q->where('name', 'like', '%' . $query->search . '%'))
            ->when($query->isActive !== null, fn ($q) => $q->where('is_active', $query->isActive))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
