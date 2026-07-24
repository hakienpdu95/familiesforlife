<?php

namespace Modules\RealEstate\Features\ListingManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\RealEstate\Models\RealEstateListing;

/** Danh sách tin của TỔ CHỨC MÌNH (tenant-scoped tự động qua TenantAwareModel, §5.5 spec Bán). */
class ListRealEstateListingsForAdminHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListRealEstateListingsForAdminQuery $query */
        return RealEstateListing::query()
            ->with('approvalSubject')
            ->when($query->listingType, fn ($q, $type) => $q->where('listing_type', $type))
            ->orderByDesc('created_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
