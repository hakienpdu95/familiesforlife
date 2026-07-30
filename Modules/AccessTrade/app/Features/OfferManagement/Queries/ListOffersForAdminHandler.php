<?php

namespace Modules\AccessTrade\Features\OfferManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\AccessTrade\Models\AccessTradeOffer;

class ListOffersForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['name', 'merchant', 'end_time', 'last_synced_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListOffersForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'end_time';
        $sortDir   = $query->sortDir === 'desc' ? 'desc' : 'asc';

        return AccessTradeOffer::query()
            ->active()
            ->when($query->merchant, fn ($q) => $q->where('merchant', $query->merchant))
            ->when($query->domain, fn ($q) => $q->where('domain', $query->domain))
            ->when($query->hasCoupon !== null, fn ($q) => $q->where('has_coupon', $query->hasCoupon))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
