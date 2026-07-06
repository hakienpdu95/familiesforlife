<?php

namespace Modules\Department\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Department\Http\Resources\DepartmentListResource;
use Modules\Department\Models\Department;
use Modules\Department\Queries\ListDepartmentsHandler;
use Modules\Department\Queries\ListDepartmentsQuery;

class DepartmentApiController extends Controller
{
    public function index(Request $request, ListDepartmentsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Department::class);

        $validated = $request->validate([
            'page'      => ['nullable', 'integer', 'min:1'],
            'size'      => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'    => ['nullable', 'string', 'max:200'],
            'branch_id' => ['nullable', 'integer'],
            'function'  => ['nullable', 'string', 'in:sales,marketing,finance,hr,it,operations,customer_service,legal,rd,other'],
            'status'    => ['nullable', 'string', 'in:active,inactive,merged'],
            'parent_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'name') : 'name';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = new ListDepartmentsQuery(
            page:      max(1, (int) ($validated['page'] ?? 1)),
            perPage:   min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir:   $sortDir,
            search:    $validated['search'] ?? null,
            branchId:  isset($validated['branch_id']) ? (int) $validated['branch_id'] : null,
            function:  $validated['function'] ?? null,
            status:    $validated['status'] ?? null,
            parentId:  isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            dateFrom:  $validated['date_from'] ?? null,
            dateTo:    $validated['date_to'] ?? null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => DepartmentListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
