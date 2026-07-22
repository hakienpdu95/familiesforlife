<?php

namespace Modules\Ocop\Features\OcopProductManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ocop\Enums\OcopProductStatus;
use Modules\Ocop\Features\OcopProductManagement\Http\Resources\OcopProductListResource;
use Modules\Ocop\Features\OcopProductManagement\Queries\ListOcopProductsForAdminHandler;
use Modules\Ocop\Features\OcopProductManagement\Queries\ListOcopProductsForAdminQuery;
use Modules\Ocop\Models\OcopProduct;

/** JSON backend cho Tabulator ở dashboard/ocop/products — cùng pattern ArticleApiController. */
class OcopProductApiController extends Controller
{
    public function index(Request $request, ListOcopProductsForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', OcopProduct::class);

        $validated = $request->validate([
            'page'        => ['nullable', 'integer', 'min:1'],
            'size'        => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'      => ['nullable', 'string', 'max:200'],
            'category_id' => ['nullable', 'integer'],
            'status'      => ['nullable', 'string', 'in:' . implode(',', array_column(OcopProductStatus::cases(), 'value'))],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListOcopProductsForAdminQuery(
            search:     $validated['search'] ?? null,
            categoryId: $validated['category_id'] ?? null,
            status:     $validated['status'] ?? null,
            page:       max(1, (int) ($validated['page'] ?? 1)),
            perPage:    min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField:  $sortField,
            sortDir:    $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => OcopProductListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
