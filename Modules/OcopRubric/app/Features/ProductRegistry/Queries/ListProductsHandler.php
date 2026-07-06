<?php

namespace Modules\OcopRubric\Features\ProductRegistry\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\OcopRubric\Models\OcopProduct;

class ListProductsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListProductsQuery $query */
        // Không cần withoutTenant() — OrganizationScope tự lọc theo tổ chức hiện tại.
        return OcopProduct::with('productGroup')
            ->when($query->status, fn ($q) => $q->where('status', $query->status))
            ->when($query->productGroupId, fn ($q) => $q->where('product_group_id', $query->productGroupId))
            ->when($query->search, fn ($q) => $q->where('name', 'like', '%' . $query->search . '%'))
            ->orderByDesc('created_at')
            ->get();
    }
}
