<?php

namespace Modules\LeadSource\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\LeadSource\Http\Resources\LeadSourceResource;
use Modules\LeadSource\Queries\ListSourcesHandler;
use Modules\LeadSource\Queries\ListSourcesQuery;

class LeadSourceApiController extends Controller
{
    /**
     * Return active sources for the current org — used by Lead dropdowns.
     */
    public function list(Request $request, ListSourcesHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', \Modules\LeadSource\Models\LeadSource::class);

        $orgId   = TenantContext::getOrganizationId() ?? abort(403);
        $sources = $handler->handle(new ListSourcesQuery($orgId, activeOnly: true));

        return response()->json([
            'data' => LeadSourceResource::collection($sources),
        ]);
    }
}
