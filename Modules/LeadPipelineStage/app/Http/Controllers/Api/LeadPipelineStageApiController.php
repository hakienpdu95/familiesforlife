<?php

namespace Modules\LeadPipelineStage\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\LeadPipelineStage\Http\Resources\LeadPipelineStageResource;
use Modules\LeadPipelineStage\Queries\ListStagesHandler;
use Modules\LeadPipelineStage\Queries\ListStagesQuery;

class LeadPipelineStageApiController extends Controller
{
    /**
     * Return active stages for the current org — used by Lead dropdowns.
     * Requires any lead view permission (checked via Policy::viewAny).
     */
    public function list(Request $request, ListStagesHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', \Modules\LeadPipelineStage\Models\LeadPipelineStage::class);

        $orgId  = TenantContext::getOrganizationId() ?? abort(403);
        $stages = $handler->handle(new ListStagesQuery($orgId, activeOnly: true));

        return response()->json([
            'data' => LeadPipelineStageResource::collection($stages),
        ]);
    }
}
