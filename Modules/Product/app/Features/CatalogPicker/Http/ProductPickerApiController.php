<?php

namespace Modules\Product\Features\CatalogPicker\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Features\CatalogPicker\Queries\BatchGetProductsHandler;
use Modules\Product\Features\CatalogPicker\Queries\BatchGetProductsQuery;
use Modules\Product\Features\CatalogPicker\Queries\SearchProductsHandler;
use Modules\Product\Features\CatalogPicker\Queries\SearchProductsQuery;
use Modules\Product\Http\Resources\ProductPickerResource;
use Modules\Product\Models\Product;

class ProductPickerApiController extends Controller
{
    public function search(Request $request, SearchProductsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $paginator = $handler->handle(new SearchProductsQuery(
            categoryId: $request->integer('category_id') ?: null,
            keyword:    $request->string('q')->value() ?: null,
            page:       max(1, $request->integer('page', 1)),
            perPage:    min(50, max(5, $request->integer('per_page', 20))),
        ));

        return response()->json([
            'data'      => ProductPickerResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }

    public function batch(Request $request, BatchGetProductsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $products = $handler->handle(new BatchGetProductsQuery($ids));

        return response()->json([
            'data' => ProductPickerResource::collection($products),
        ]);
    }
}
