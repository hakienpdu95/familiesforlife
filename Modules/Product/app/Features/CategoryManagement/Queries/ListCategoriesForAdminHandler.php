<?php

namespace Modules\Product\Features\CategoryManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Product\Models\ProductCategory;

class ListCategoriesForAdminHandler implements QueryHandlerInterface
{
    /** Danh sách phẳng (kèm parent + đếm sản phẩm) — dùng cho select cascading & bảng quản trị. */
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListCategoriesForAdminQuery $query */
        return ProductCategory::query()
            ->with('parent:id,name')
            ->withCount('products')
            ->when($query->search, fn ($q) => $q->where('name', 'like', '%' . $query->search . '%'))
            ->orderBy('sort_order')
            ->get();
    }
}
