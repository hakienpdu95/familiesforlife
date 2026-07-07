<?php

namespace Modules\Post\Features\CategoryManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Post\Models\PostCategory;

class ListCategoriesForAdminHandler implements QueryHandlerInterface
{
    /** Danh sách phẳng (kèm parent + đếm bài viết) — dùng cho select cascading & bảng quản trị. */
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListCategoriesForAdminQuery $query */
        return PostCategory::query()
            ->with('parent:id,name')
            ->withCount('articles')
            ->when($query->search, fn ($q) => $q->where('name', 'like', '%' . $query->search . '%'))
            ->orderBy('sort_order')
            ->get();
    }
}
