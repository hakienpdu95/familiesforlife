<?php

namespace Modules\N8n\Features\ConnectionManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\N8n\Models\N8nConnection;

class ListN8nConnectionsForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'inbound_enabled', 'outbound_enabled', 'last_inbound_at', 'last_outbound_at', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListN8nConnectionsForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return N8nConnection::query()
            ->when($query->includeTrashed, fn ($q) => $q->withTrashed())
            ->when($query->search, fn ($q) => $q->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query->search}%")
                    ->orWhere('purpose_note', 'like', "%{$query->search}%");
            }))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
