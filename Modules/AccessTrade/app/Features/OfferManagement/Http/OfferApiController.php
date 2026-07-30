<?php

namespace Modules\AccessTrade\Features\OfferManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AccessTrade\Features\OfferManagement\Http\Resources\OfferListResource;
use Modules\AccessTrade\Features\OfferManagement\Queries\ListOffersForAdminHandler;
use Modules\AccessTrade\Features\OfferManagement\Queries\ListOffersForAdminQuery;

/** JSON backend cho Tabulator ở dashboard/accesstrade/offers — cùng pattern BannerApiController. */
class OfferApiController extends Controller
{
    public function index(Request $request, ListOffersForAdminHandler $handler): JsonResponse
    {
        $validated = $request->validate([
            'page'       => ['nullable', 'integer', 'min:1'],
            'size'       => ['nullable', 'integer', 'min:5', 'max:100'],
            'merchant'   => ['nullable', 'string', 'max:120'],
            'domain'     => ['nullable', 'string', 'max:255'],
            'has_coupon' => ['nullable', 'boolean'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'end_time') : 'end_time';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = new ListOffersForAdminQuery(
            merchant:  $validated['merchant'] ?? null,
            domain:    $validated['domain'] ?? null,
            hasCoupon: array_key_exists('has_coupon', $validated) ? filter_var($validated['has_coupon'], FILTER_VALIDATE_BOOLEAN) : null,
            page:      max(1, (int) ($validated['page'] ?? 1)),
            perPage:   min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir:   $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => OfferListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
