<?php

namespace Modules\Newsletter\Features\BroadcastSending\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Newsletter\Models\NewsletterBroadcastLog;

class ListBroadcastLogsForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['subject', 'created_at', 'scheduled_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListBroadcastLogsForAdminQuery $query */
        $q = NewsletterBroadcastLog::query()->with('sentBy:id,name');

        if ($query->search) {
            $q->where('subject', 'like', '%' . $query->search . '%');
        }

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return $q->orderBy($sortField, $sortDir)
            ->orderByDesc('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
