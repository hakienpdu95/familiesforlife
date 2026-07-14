<?php

namespace Modules\Event\Features\EventModeration\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Event\Models\Event;

class ListEventsForAdminHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListEventsForAdminQuery $query */
        return Event::query()
            ->with('category:id,name,color_hex')
            ->when($query->search, fn ($q) => $q->where('title', 'like', '%' . $query->search . '%'))
            ->when($query->status, fn ($q) => $q->where('status', $query->status))
            ->orderByDesc('created_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
