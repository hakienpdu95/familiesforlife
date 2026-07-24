<?php

namespace Modules\RealEstate\Features\ListingManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\RealEstate\Models\RealEstateListing;

/**
 * Danh sách tin của TỔ CHỨC MÌNH (tenant-scoped tự động qua TenantAwareModel, §5.5 spec Bán) —
 * cùng pattern ListProductsForAdminHandler (Modules/Product), phục vụ Tabulator remote
 * pagination/sort/filter ở RealEstateListingApiController.
 */
class ListRealEstateListingsForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['title', 'area', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListRealEstateListingsForAdminQuery $query */
        $q = RealEstateListing::query()->with('approvalSubject');

        if ($query->search) {
            $q->where('title', 'like', '%' . $query->search . '%');
        }

        if ($query->listingType) {
            $q->where('listing_type', $query->listingType);
        }

        if ($query->propertyType) {
            $q->where('property_type', $query->propertyType);
        }

        if ($query->approvalStatus) {
            $status = $query->approvalStatus;
            $q->whereHas('approvalSubject', fn ($sub) => $sub->where('status', $status));
        }

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q->orderBy($sortField, $sortDir)->orderBy('id', $sortDir);

        return $q->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
