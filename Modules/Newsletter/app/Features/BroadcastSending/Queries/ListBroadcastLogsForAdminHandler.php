<?php

namespace Modules\Newsletter\Features\BroadcastSending\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Newsletter\Models\NewsletterBroadcastLog;

class ListBroadcastLogsForAdminHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListBroadcastLogsForAdminQuery $query */
        return NewsletterBroadcastLog::query()
            ->with('sentBy:id,name')
            ->orderByDesc('created_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
