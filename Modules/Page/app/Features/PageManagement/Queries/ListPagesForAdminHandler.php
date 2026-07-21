<?php

namespace Modules\Page\Features\PageManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Page\Models\Page;

class ListPagesForAdminHandler implements QueryHandlerInterface
{
    /** Danh sách phẳng (Page không phân cấp) — mới cập nhật lên trước. */
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPagesForAdminQuery $query */
        return Page::query()
            ->when($query->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('title', 'like', "%{$query->search}%")
                ->orWhere('slug', 'like', "%{$query->search}%")))
            ->when($query->status, fn ($q) => $q->where('status', $query->status))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();
    }
}
