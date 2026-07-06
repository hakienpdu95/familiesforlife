<?php

namespace Modules\JobPosting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\JobPosting\Models\JpSkillMaster;

class JpSkillMasterApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orgId = TenantContext::getOrganizationId();
        $search = $request->input('q', '');

        $query = JpSkillMaster::withoutTenant()
            ->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->where('is_active', true);

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $skills = $query->orderBy('category')->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'category']);

        return response()->json($skills);
    }
}
