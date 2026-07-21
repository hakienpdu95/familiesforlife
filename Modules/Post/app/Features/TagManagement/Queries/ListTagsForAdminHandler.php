<?php

namespace Modules\Post\Features\TagManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Post\Models\PostTag;

class ListTagsForAdminHandler implements QueryHandlerInterface
{
    /**
     * Danh sách phẳng kèm đếm số bài viết — dùng `withCount('articles')` để tránh N+1
     * (spec/PostTag_Management_Technical_Specification.md §3.8).
     */
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListTagsForAdminQuery $query */
        return PostTag::query()
            ->withCount('articles')
            ->when($query->search, fn ($q) => $q->where('name', 'like', '%' . $query->search . '%'))
            ->orderBy('name')
            ->get();
    }
}
