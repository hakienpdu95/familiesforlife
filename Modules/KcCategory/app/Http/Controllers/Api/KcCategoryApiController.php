<?php

namespace Modules\KcCategory\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\KcCategory\Http\Resources\KcCategoryListResource;
use Modules\KcCategory\Models\KcCategory;
use Modules\KcCategory\Queries\ListKcCategoriesHandler;
use Modules\KcCategory\Queries\ListKcCategoriesQuery;

class KcCategoryApiController extends Controller
{
    public function index(Request $request, ListKcCategoriesHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', KcCategory::class);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'sort_order') : 'sort_order';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $isActive = null;
        if ($request->filled('is_active')) {
            $isActive = in_array($request->input('is_active'), ['1', 'true'], true);
        }

        $parentId = null;
        if ($request->filled('parent_id')) {
            $parentId = (int) $request->input('parent_id');
        }

        $query = new ListKcCategoriesQuery(
            page:      max(1, (int) ($request->input('page', 1))),
            perPage:   min(100, max(5, (int) ($request->input('size', 25)))),
            sortField: $sortField,
            sortDir:   $sortDir,
            search:    $request->input('search'),
            isActive:  $isActive,
            parentId:  $parentId,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => KcCategoryListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
