<?php

namespace Modules\Post\Features\TagManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Post\Models\PostTag;

class ListTagsForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'articles_count'];

    /**
     * Danh sách phân trang kèm đếm số bài viết — dùng `withCount('articles')` để tránh N+1
     * (spec/PostTag_Management_Technical_Specification.md §3.8).
     */
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListTagsForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'name';
        $sortDir   = $query->sortDir === 'desc' ? 'desc' : 'asc';

        return PostTag::query()
            ->withCount('articles')
            ->when($query->search, fn ($q) => $q->where('name', 'like', '%' . $query->search . '%'))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
