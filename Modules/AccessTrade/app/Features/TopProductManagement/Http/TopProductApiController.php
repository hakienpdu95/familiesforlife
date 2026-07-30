<?php

namespace Modules\AccessTrade\Features\TopProductManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AccessTrade\Features\TopProductManagement\Http\Resources\TopProductListResource;
use Modules\AccessTrade\Features\TopProductManagement\Queries\ListTopProductsForAdminHandler;
use Modules\AccessTrade\Features\TopProductManagement\Queries\ListTopProductsForAdminQuery;

/** JSON backend cho Tabulator ở dashboard/accesstrade/top-products — cùng pattern OfferApiController. */
class TopProductApiController extends Controller
{
    public function index(Request $request, ListTopProductsForAdminHandler $handler): JsonResponse
    {
        $validated = $request->validate([
            'page'     => ['nullable', 'integer', 'min:1'],
            'size'     => ['nullable', 'integer', 'min:5', 'max:100'],
            'merchant' => ['nullable', 'string', 'max:120'],
            'brand'    => ['nullable', 'string', 'max:255'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'total') : 'total';
        $sortDir   = is_array($sortRaw) ? (string) ($sortRaw['dir'] ?? 'desc') : 'desc';

        $query = new ListTopProductsForAdminQuery(
            merchant:  $validated['merchant'] ?? null,
            brand:     $validated['brand'] ?? null,
            page:      max(1, (int) ($validated['page'] ?? 1)),
            perPage:   min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir:   $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => TopProductListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
