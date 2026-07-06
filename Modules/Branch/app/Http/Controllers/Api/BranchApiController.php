<?php

namespace Modules\Branch\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Branch\Http\Resources\BranchListResource;
use Modules\Branch\Models\Branch;
use Modules\Branch\Queries\ListBranchesHandler;
use Modules\Branch\Queries\ListBranchesQuery;

class BranchApiController extends Controller
{
    public function index(Request $request, ListBranchesHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Branch::class);

        $validated = $request->validate([
            'page'          => ['nullable', 'integer', 'min:1'],
            'size'          => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'        => ['nullable', 'string', 'max:200'],
            'type'          => ['nullable', 'string', 'in:headquarters,regional_office,branch,store,warehouse'],
            'status'        => ['nullable', 'string', 'in:active,inactive,closed'],
            'province_code' => ['nullable', 'string', 'max:10'],
            'parent_id'     => ['nullable', 'integer'],
            'date_from'     => ['nullable', 'date_format:Y-m-d'],
            'date_to'       => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'name') : 'name';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = new ListBranchesQuery(
            page:         max(1, (int) ($validated['page'] ?? 1)),
            perPage:      min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField:    $sortField,
            sortDir:      $sortDir,
            search:       $validated['search'] ?? null,
            type:         $validated['type'] ?? null,
            status:       $validated['status'] ?? null,
            provinceCode: $validated['province_code'] ?? null,
            parentId:     isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            dateFrom:     $validated['date_from'] ?? null,
            dateTo:       $validated['date_to'] ?? null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => BranchListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
