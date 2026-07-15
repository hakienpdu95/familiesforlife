<?php

namespace Modules\Ocop\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Ocop\Models\OcopProduct;

class ListPublishedOcopProductsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPublishedOcopProductsQuery $query */
        return OcopProduct::published()
            ->with('category')
            ->when($query->provinceCode, fn ($q) => $q->where('province_code', $query->provinceCode))
            ->when($query->categoryId, fn ($q) => $q->where('category_id', $query->categoryId))
            ->when($query->search, fn ($q) => $q->where('name', 'like', "%{$query->search}%"))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
