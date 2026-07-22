<?php

namespace Modules\Product\Features\CategoryManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Product\Models\ProductCategory;

class GetCategoryTreeHandler implements QueryHandlerInterface
{
    /** Cây đầy đủ dựng từ danh sách phẳng (1 query) — tránh N+1 khi tải children đệ quy. */
    public function handle(QueryInterface $query): Collection
    {
        /** @var GetCategoryTreeQuery $query */
        $all = ProductCategory::query()
            ->withCount('products')
            ->when($query->activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get();

        // groupBy dùng parent_id làm key — null bị PHP ép thành '' khi làm array key,
        // nên tách riêng nhóm root bằng whereNull thay vì trông cậy vào groupBy(null).
        $byParent = $all->whereNotNull('parent_id')->groupBy('parent_id');

        $attachChildren = function (ProductCategory $node) use (&$attachChildren, $byParent): ProductCategory {
            $children = $byParent->get($node->id, collect());
            $node->setRelation('children', $children->map($attachChildren)->values());

            return $node;
        };

        return $all->whereNull('parent_id')->map($attachChildren)->values();
    }
}
