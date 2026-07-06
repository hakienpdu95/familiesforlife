<?php

namespace Modules\Recruitment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Recruitment\Http\Resources\ApplicationListResource;
use Modules\Recruitment\Queries\KanbanBoardHandler;
use Modules\Recruitment\Queries\KanbanBoardQuery;
use Modules\Recruitment\Queries\ListApplicationsHandler;
use Modules\Recruitment\Queries\ListApplicationsQuery;

class ApplicationApiController extends Controller
{
    public function index(Request $request, ListApplicationsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Recruitment\Models\RcApplication::class);

        $sort      = $request->input('sort', []);
        $sortField = $sort[0]['field'] ?? 'applied_at';
        $sortDir   = $sort[0]['dir']   ?? 'desc';

        $query = new ListApplicationsQuery(
            page:        max(1, $request->integer('page', 1)),
            perPage:     min(100, max(5, $request->integer('size', 25))),
            sortField:   $sortField,
            sortDir:     $sortDir,
            search:      $request->input('search'),
            status:      $request->input('status'),
            stageId:     $request->input('stage_id'),
            jpJobPostId: $request->input('jp_job_post_id'),
            assignedTo:  $request->input('assigned_to'),
            dateFrom:    $request->input('date_from'),
            dateTo:      $request->input('date_to'),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => ApplicationListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }

    public function board(Request $request, string $jpJobPostUuid, KanbanBoardHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Recruitment\Models\RcApplication::class);

        $orgId = auth()->user()->organization_id ?? 0;

        $query = new KanbanBoardQuery(
            orgId:        $orgId,
            jpJobPostUuid: $jpJobPostUuid,
        );

        $stages = $handler->handle($query);

        return response()->json(['data' => $stages]);
    }
}
