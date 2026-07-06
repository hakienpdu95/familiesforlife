<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserOptionsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            $orgId = $userOrgId;
        } else {
            $orgId = $request->integer('organization_id') ?: TenantContext::getOrganizationId();
        }

        $q = $request->input('q', '');

        $rows = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name']);

        return response()->json($rows->map(fn ($u) => ['id' => $u->id, 'text' => $u->name]));
    }
}
