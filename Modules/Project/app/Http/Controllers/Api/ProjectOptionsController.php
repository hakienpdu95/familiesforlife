<?php

namespace Modules\Project\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\Enums\ProjectStatus;
use Modules\Project\Models\Project;

class ProjectOptionsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            $orgId = $userOrgId;
        } else {
            $orgId = $request->integer('organization_id') ?: TenantContext::getOrganizationId();
        }

        $rows = Project::withoutTenant()
            ->where('organization_id', $orgId)
            ->whereIn('status', [ProjectStatus::Active->value, ProjectStatus::Planning->value])
            ->when($request->input('q'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'code']);

        return response()->json($rows->map(fn ($p) => [
            'id'   => $p->id,
            'text' => $p->name . ' (' . $p->code . ')',
        ]));
    }
}
