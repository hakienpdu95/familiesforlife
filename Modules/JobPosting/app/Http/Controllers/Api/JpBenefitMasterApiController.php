<?php

namespace Modules\JobPosting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Modules\JobPosting\Models\JpBenefitMaster;

class JpBenefitMasterApiController extends Controller
{
    public function index(): JsonResponse
    {
        $orgId = TenantContext::getOrganizationId();

        $benefits = JpBenefitMaster::withoutTenant()
            ->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'name', 'icon', 'category']);

        return response()->json($benefits);
    }
}
