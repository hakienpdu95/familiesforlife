<?php

namespace Modules\Ocop\Features\OcopProductManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Ocop\Models\OcopProduct;

class ListOcopProductsForAdminHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListOcopProductsForAdminQuery $query */
        return OcopProduct::query()
            ->with('category:id,name')
            ->when($query->search, fn ($q) => $q->where('name', 'like', "%{$query->search}%"))
            ->when($query->categoryId, fn ($q) => $q->where('category_id', $query->categoryId))
            ->when($query->status, fn ($q) => $q->where('status', $query->status))
            ->when($query->provinceCode, fn ($q) => $q->where('province_code', $query->provinceCode))
            ->orderByDesc('created_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
