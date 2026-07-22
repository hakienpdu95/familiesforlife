<?php

namespace Modules\Page\Features\PageManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Page\Models\Page;

class ListPagesForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['title', 'slug', 'status', 'updated_at'];

    /** Danh sách phẳng (Page không phân cấp). */
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPagesForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'updated_at';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return Page::query()
            ->when($query->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('title', 'like', "%{$query->search}%")
                ->orWhere('slug', 'like', "%{$query->search}%")))
            ->when($query->status, fn ($q) => $q->where('status', $query->status))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
