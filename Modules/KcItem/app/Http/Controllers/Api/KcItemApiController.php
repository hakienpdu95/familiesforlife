<?php

namespace Modules\KcItem\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\KcItem\Http\Resources\KcItemListResource;
use Modules\KcItem\Models\KcItem;
use Modules\KcItem\Queries\ListKcItemsHandler;
use Modules\KcItem\Queries\ListKcItemsQuery;

class KcItemApiController extends Controller
{
    public function index(Request $request, ListKcItemsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', KcItem::class);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $categoryId = null;
        if ($request->filled('category_id')) {
            $categoryId = (int) $request->input('category_id');
        }

        $tagId = null;
        if ($request->filled('tag_id')) {
            $tagId = (int) $request->input('tag_id');
        }

        $query = new ListKcItemsQuery(
            page:       max(1, (int) ($request->input('page', 1))),
            perPage:    min(100, max(5, (int) ($request->input('size', 25)))),
            sortField:  $sortField,
            sortDir:    $sortDir,
            search:     $request->input('search'),
            status:     $request->input('status'),
            type:       $request->input('type'),
            categoryId: $categoryId,
            visibility: $request->input('visibility'),
            dateFrom:   $request->input('date_from'),
            dateTo:     $request->input('date_to'),
            tagId:      $tagId,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => KcItemListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
