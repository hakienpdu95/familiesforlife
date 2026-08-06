<?php

namespace Modules\N8n\Features\LogViewing\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\N8n\Models\N8nOutboundLog;

class ListN8nOutboundLogsHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['requested_at', 'http_status', 'duration_ms', 'event_name'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListN8nOutboundLogsQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'requested_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return N8nOutboundLog::query()
            ->with('connection')
            ->when($query->connectionId, fn ($q) => $q->where('connection_id', $query->connectionId))
            ->when(! is_null($query->success), fn ($q) => $q->where('success', $query->success))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id', 'desc')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
