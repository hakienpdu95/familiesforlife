<?php

namespace Modules\Product\Features\CatalogPicker\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Models\Product;

class SearchProductsHandler implements QueryHandlerInterface
{
    /** Chỉ trả sản phẩm còn "sống" (active|out_of_stock) — dùng đúng index idx_product_org_cat_status/idx_product_org_name. */
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var SearchProductsQuery $query */
        return Product::query()
            ->whereIn('status', [ProductStatus::Active->value, ProductStatus::OutOfStock->value])
            ->when($query->categoryId, fn ($q) => $q->where('category_id', $query->categoryId))
            ->when($query->keyword, fn ($q) => $q->where('name', 'like', $query->keyword . '%'))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
