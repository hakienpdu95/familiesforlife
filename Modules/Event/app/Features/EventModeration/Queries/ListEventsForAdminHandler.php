<?php

namespace Modules\Event\Features\EventModeration\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Event\Models\Event;

class ListEventsForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['title', 'start_date', 'status', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListEventsForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return Event::query()
            ->with('category:id,name,color_hex')
            ->when($query->search, fn ($q) => $q->where('title', 'like', '%' . $query->search . '%'))
            ->when($query->status, fn ($q) => $q->where('status', $query->status))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
