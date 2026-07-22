<?php

namespace Modules\Product\Features\CatalogManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Modules\Product\Features\CatalogManagement\Http\Resources\ProductListResource;
use Modules\Product\Features\CatalogManagement\Queries\ListProductsForAdminHandler;
use Modules\Product\Features\CatalogManagement\Queries\ListProductsForAdminQuery;
use Modules\Product\Models\Product;

/**
 * JSON backend cho Tabulator ở dashboard/products — cùng pattern
 * Modules/Organization/app/Http/Controllers/Api/OrganizationApiController (tham chiếu theo
 * yêu cầu): remote pagination/sort/filter, trả {data, last_page, total}. Tenant-scoped tự động
 * qua TenantAwareModel (Product) — không cần lọc organization_id tường minh ở đây.
 */
class ProductApiController extends Controller
{
    public function index(Request $request, ListProductsForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $validated = $request->validate([
            'page'        => ['nullable', 'integer', 'min:1'],
            'size'        => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'      => ['nullable', 'string', 'max:200'],
            'category_id' => ['nullable', 'integer'],
            'status'      => ['nullable', 'string', 'in:' . implode(',', array_column(ProductStatus::cases(), 'value'))],
            'type'        => ['nullable', 'string', 'in:' . implode(',', array_column(ProductType::cases(), 'value'))],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'sort_order') : 'sort_order';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = new ListProductsForAdminQuery(
            page:       max(1, (int) ($validated['page'] ?? 1)),
            perPage:    min(100, max(5, (int) ($validated['size'] ?? 25))),
            search:     $validated['search'] ?? null,
            categoryId: $validated['category_id'] ?? null,
            status:     $validated['status'] ?? null,
            type:       $validated['type'] ?? null,
            sortField:  $sortField,
            sortDir:    $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => ProductListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
