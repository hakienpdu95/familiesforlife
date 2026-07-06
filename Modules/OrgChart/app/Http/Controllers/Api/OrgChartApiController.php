<?php

namespace Modules\OrgChart\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\OrgChart\Http\Resources\OrgChartConfigListResource;
use Modules\OrgChart\Models\OrgChartConfig;
use Modules\OrgChart\Queries\GetOrgChartTreeHandler;
use Modules\OrgChart\Queries\GetOrgChartTreeQuery;
use Modules\OrgChart\Queries\ListOrgChartConfigsHandler;
use Modules\OrgChart\Queries\ListOrgChartConfigsQuery;

class OrgChartApiController extends Controller
{
    public function index(Request $request, ListOrgChartConfigsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', OrgChartConfig::class);

        $sort      = $request->input('sort', []);
        $sortField = $sort[0]['field'] ?? 'is_default';
        $sortDir   = $sort[0]['dir']   ?? 'desc';

        $query = new ListOrgChartConfigsQuery(
            page:      max(1, $request->integer('page', 1)),
            perPage:   min(100, max(5, $request->integer('size', 25))),
            sortField: $sortField,
            sortDir:   $sortDir,
            search:    $request->input('search'),
            viewType:  $request->input('view_type'),
            groupBy:   $request->input('group_by'),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => OrgChartConfigListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }

    public function tree(OrgChartConfig $orgChartConfig, GetOrgChartTreeHandler $handler): JsonResponse
    {
        $this->authorize('view', $orgChartConfig);

        $result = $handler->handle(new GetOrgChartTreeQuery($orgChartConfig));

        return response()->json($result);
    }
}
