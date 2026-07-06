<?php

namespace Modules\JobTitle\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\JobTitle\Http\Resources\JobTitleListResource;
use Modules\JobTitle\Models\JobTitle;
use Modules\JobTitle\Queries\ListJobTitlesHandler;
use Modules\JobTitle\Queries\ListJobTitlesQuery;

class JobTitleApiController extends Controller
{
    public function index(Request $request, ListJobTitlesHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', JobTitle::class);

        $validated = $request->validate([
            'page'      => ['nullable', 'integer', 'min:1'],
            'size'      => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'    => ['nullable', 'string', 'max:200'],
            'category'  => ['nullable', 'string', 'in:executive,manager,supervisor,staff,intern,consultant'],
            'is_active' => ['nullable', 'in:0,1,true,false'],
            'level_min' => ['nullable', 'integer', 'between:1,20'],
            'level_max' => ['nullable', 'integer', 'between:1,20'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'level') : 'level';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $isActive = null;
        if (isset($validated['is_active'])) {
            $isActive = in_array($validated['is_active'], ['1', 'true'], true);
        }

        $query = new ListJobTitlesQuery(
            page:      max(1, (int) ($validated['page'] ?? 1)),
            perPage:   min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir:   $sortDir,
            search:    $validated['search'] ?? null,
            category:  $validated['category'] ?? null,
            isActive:  $isActive,
            levelMin:  isset($validated['level_min']) ? (int) $validated['level_min'] : null,
            levelMax:  isset($validated['level_max']) ? (int) $validated['level_max'] : null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => JobTitleListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
