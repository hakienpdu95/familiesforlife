<?php

namespace Modules\N8n\Features\LogViewing\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\N8n\Models\N8nInboundLog;

class ListN8nInboundLogsHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['received_at', 'http_status_returned', 'event_name'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListN8nInboundLogsQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'received_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return N8nInboundLog::query()
            ->with('connection')
            ->when($query->connectionId, fn ($q) => $q->where('connection_id', $query->connectionId))
            ->when(! is_null($query->signatureValid), fn ($q) => $q->where('signature_valid', $query->signatureValid))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id', 'desc')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
