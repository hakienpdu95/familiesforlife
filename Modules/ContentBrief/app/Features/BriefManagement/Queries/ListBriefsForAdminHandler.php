<?php

namespace Modules\ContentBrief\Features\BriefManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\ContentBrief\Models\ContentBrief;

class ListBriefsForAdminHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListBriefsForAdminQuery $query */
        return ContentBrief::query()
            ->with(['assignee:id,name', 'currentVersion:id,content_brief_id,version_number,status'])
            ->withCount('versions')
            ->when($query->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('title', 'like', "%{$query->search}%")
                ->orWhere('target_keyword', 'like', "%{$query->search}%")))
            ->when($query->status, fn ($q) => $q->where('status', $query->status))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();
    }
}
