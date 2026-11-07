<?php

namespace Modules\Ocop\Features\OcopProductManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Ocop\Models\OcopCategory;
use Modules\Ocop\Models\OcopProduct;

class ListOcopProductsForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'star_rating', 'status', 'sort_order', 'created_at', 'deleted_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListOcopProductsForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : ($query->trashed ? 'deleted_at' : 'created_at');
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return OcopProduct::query()
            ->when($query->trashed, fn ($q) => $q->onlyTrashed())
            ->with(['category:id,name', 'qrIdentity'])
            ->when($query->search, fn ($q) => $q->where('name', 'like', "%{$query->search}%"))
            ->when($query->categoryId, fn ($q) => $q->whereIn('category_id', OcopCategory::selfAndDescendantIds($query->categoryId) ?: [$query->categoryId]))
            ->when($query->status, fn ($q) => $q->where('status', $query->status))
            ->when($query->provinceCode, fn ($q) => $q->where('province_code', $query->provinceCode))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
