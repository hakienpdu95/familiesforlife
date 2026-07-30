<?php

namespace Modules\AccessTrade\Features\TopProductManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\AccessTrade\Models\AccessTradeTopProduct;

class ListTopProductsForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'merchant', 'price', 'discount', 'total', 'last_synced_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListTopProductsForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'total';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return AccessTradeTopProduct::query()
            ->forMerchant($query->merchant)
            ->when($query->brand, fn ($q) => $q->where('brand', $query->brand))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
