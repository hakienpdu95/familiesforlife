<?php

namespace Modules\RealEstate\Features\ListingManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\RealEstate\Enums\ListingType;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Features\ListingManagement\Http\Resources\RealEstateListingListResource;
use Modules\RealEstate\Features\ListingManagement\Queries\ListRealEstateListingsForAdminHandler;
use Modules\RealEstate\Features\ListingManagement\Queries\ListRealEstateListingsForAdminQuery;
use Modules\RealEstate\Models\RealEstateListing;

/**
 * JSON backend cho Tabulator ở dashboard/real-estate — cùng pattern
 * Modules/Product/app/Features/CatalogManagement/Http/ProductApiController (tham chiếu theo
 * yêu cầu): remote pagination/sort/filter, trả {data, last_page, total}. Tenant-scoped tự động
 * qua TenantAwareModel (RealEstateListing) — không cần lọc organization_id tường minh ở đây.
 */
class RealEstateListingApiController extends Controller
{
    public function index(Request $request, ListRealEstateListingsForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', RealEstateListing::class);

        $validated = $request->validate([
            'page'            => ['nullable', 'integer', 'min:1'],
            'size'            => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'          => ['nullable', 'string', 'max:200'],
            'listing_type'    => ['nullable', 'string', 'in:' . implode(',', array_column(ListingType::cases(), 'value'))],
            'property_type'   => ['nullable', 'string', 'in:' . implode(',', array_column(PropertyType::cases(), 'value'))],
            'approval_status' => ['nullable', 'string', 'in:' . implode(',', array_column(ApprovalStatus::cases(), 'value'))],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListRealEstateListingsForAdminQuery(
            listingType:    isset($validated['listing_type']) ? ListingType::from($validated['listing_type']) : null,
            propertyType:   $validated['property_type'] ?? null,
            approvalStatus: $validated['approval_status'] ?? null,
            search:         $validated['search'] ?? null,
            page:           max(1, (int) ($validated['page'] ?? 1)),
            perPage:        min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField:      $sortField,
            sortDir:        $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => RealEstateListingListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
