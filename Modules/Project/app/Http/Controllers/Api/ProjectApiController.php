<?php

namespace Modules\Project\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\Http\Resources\ProjectListResource;
use Modules\Project\Queries\ListProjectsHandler;
use Modules\Project\Queries\ListProjectsQuery;

class ProjectApiController extends Controller
{
    public function index(Request $request, ListProjectsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Project\Models\Project::class);

        $sort      = $request->input('sort', []);
        $sortField = $sort[0]['field'] ?? 'name';
        $sortDir   = $sort[0]['dir']   ?? 'asc';

        $query = new ListProjectsQuery(
            page:         max(1, $request->integer('page', 1)),
            perPage:      min(100, max(5, $request->integer('size', 25))),
            sortField:    $sortField,
            sortDir:      $sortDir,
            search:       $request->input('search'),
            status:       $request->input('status'),
            priority:     $request->input('priority'),
            category:     $request->input('category'),
            branchId:     $request->filled('branch_id')     ? (int) $request->input('branch_id')     : null,
            departmentId: $request->filled('department_id') ? (int) $request->input('department_id') : null,
            ownerId:      $request->filled('owner_id')      ? (int) $request->input('owner_id')      : null,
            dateFrom:     $request->input('date_from'),
            dateTo:       $request->input('date_to'),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => ProjectListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
